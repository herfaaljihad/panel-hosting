<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use App\Models\EmailAccount;

class EmailServerService
{
    protected string $postfixConfigPath;
    protected string $dovecotConfigPath;
    protected string $mailPath;

    public function __construct()
    {
        $this->postfixConfigPath = config('hosting.mail_config_path', '/etc/postfix');
        $this->dovecotConfigPath = '/etc/dovecot';
        $this->mailPath = config('hosting.mail_spool_path', '/var/mail');
    }

    /**
     * Create email account with full server integration
     */
    public function createEmailAccount(EmailAccount $emailAccount): bool
    {
        try {
            // Create system user for email
            $username = str_replace('@', '_', $emailAccount->email);
            $domain = explode('@', $emailAccount->email)[1];
            $userHome = "{$this->mailPath}/{$domain}/{$username}";

            // Create mail directory structure
            $this->createMailDirectories($userHome);

            // Add to Postfix virtual maps
            $this->addToVirtualMaps($emailAccount->email, $username);

            // Configure Dovecot user
            $this->configureDovecotUser($emailAccount);

            // Set password
            $this->setEmailPassword($emailAccount->email, $emailAccount->password);

            // Reload mail services
            $this->reloadMailServices();

            Log::channel('performance')->info('Email account created', [
                'email' => $emailAccount->email,
                'domain' => $domain
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Email account creation failed', [
                'email' => $emailAccount->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete email account
     */
    public function deleteEmailAccount(EmailAccount $emailAccount): bool
    {
        try {
            $username = str_replace('@', '_', $emailAccount->email);
            $domain = explode('@', $emailAccount->email)[1];
            $userHome = "{$this->mailPath}/{$domain}/{$username}";

            // Remove from virtual maps
            $this->removeFromVirtualMaps($emailAccount->email);

            // Remove mail directory
            if (file_exists($userHome)) {
                Process::run("rm -rf {$userHome}");
            }

            // Reload mail services
            $this->reloadMailServices();

            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Email account deletion failed', [
                'email' => $emailAccount->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create mail directory structure
     */
    private function createMailDirectories(string $userHome): void
    {
        $directories = [
            $userHome,
            "{$userHome}/Maildir",
            "{$userHome}/Maildir/cur",
            "{$userHome}/Maildir/new",
            "{$userHome}/Maildir/tmp",
            "{$userHome}/Maildir/.Sent",
            "{$userHome}/Maildir/.Drafts",
            "{$userHome}/Maildir/.Trash",
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0750, true);
            }
        }

        // Set proper ownership
        Process::run("chown -R mail:mail {$userHome}");
        Process::run("chmod -R 750 {$userHome}");
    }

    /**
     * Add email to Postfix virtual maps
     */
    private function addToVirtualMaps(string $email, string $username): void
    {
        $virtualFile = "{$this->postfixConfigPath}/virtual";
        $virtualAliasFile = "{$this->postfixConfigPath}/virtual_alias";

        // Add to virtual domains if not exists
        $domain = explode('@', $email)[1];
        $virtualDomainsFile = "{$this->postfixConfigPath}/virtual_domains";
        
        if (!file_exists($virtualDomainsFile) || !str_contains(file_get_contents($virtualDomainsFile), $domain)) {
            file_put_contents($virtualDomainsFile, "{$domain}\n", FILE_APPEND);
        }

        // Add email mapping
        file_put_contents($virtualFile, "{$email} {$username}\n", FILE_APPEND);

        // Rebuild maps
        Process::run("postmap {$virtualFile}");
        Process::run("postmap {$virtualDomainsFile}");
    }

    /**
     * Remove from virtual maps
     */
    private function removeFromVirtualMaps(string $email): void
    {
        $virtualFile = "{$this->postfixConfigPath}/virtual";
        
        if (file_exists($virtualFile)) {
            $content = file_get_contents($virtualFile);
            $content = preg_replace("/^{$email}.*\n/m", '', $content);
            file_put_contents($virtualFile, $content);
            Process::run("postmap {$virtualFile}");
        }
    }

    /**
     * Configure Dovecot user
     */
    private function configureDovecotUser(EmailAccount $emailAccount): void
    {
        $username = str_replace('@', '_', $emailAccount->email);
        $domain = explode('@', $emailAccount->email)[1];
        $userHome = "{$this->mailPath}/{$domain}/{$username}";

        $userConfig = [
            'user' => $emailAccount->email,
            'password' => $emailAccount->password,
            'home' => $userHome,
            'mail' => "maildir:{$userHome}/Maildir",
        ];

        // Add to Dovecot passwd file
        $passwdFile = "{$this->dovecotConfigPath}/users";
        $passwdLine = implode(':', [
            $emailAccount->email,
            password_hash($emailAccount->password, PASSWORD_BCRYPT),
            1000, // uid
            1000, // gid
            $emailAccount->email,
            $userHome,
            '/bin/false'
        ]);

        file_put_contents($passwdFile, $passwdLine . "\n", FILE_APPEND);
    }

    /**
     * Set email password
     */
    private function setEmailPassword(string $email, string $password): void
    {
        // Using doveadm to set password
        Process::run("doveadm pw -s CRYPT -p {$password}");
    }

    /**
     * Reload mail services
     */
    private function reloadMailServices(): void
    {
        Process::run("systemctl reload postfix");
        Process::run("systemctl reload dovecot");
    }

    /**
     * Get email statistics
     */
    public function getEmailStats(string $email): array
    {
        $username = str_replace('@', '_', $email);
        $domain = explode('@', $email)[1];
        $userHome = "{$this->mailPath}/{$domain}/{$username}";

        $stats = [
            'total_messages' => 0,
            'total_size' => 0,
            'new_messages' => 0,
            'folders' => []
        ];

        if (file_exists("{$userHome}/Maildir")) {
            // Count messages in each folder
            $folders = ['new', 'cur'];
            foreach ($folders as $folder) {
                $folderPath = "{$userHome}/Maildir/{$folder}";
                if (file_exists($folderPath)) {
                    $files = glob("{$folderPath}/*");
                    $count = count($files);
                    $stats['folders'][$folder] = $count;
                    $stats['total_messages'] += $count;
                    
                    if ($folder === 'new') {
                        $stats['new_messages'] = $count;
                    }
                }
            }

            // Calculate total size
            $result = Process::run("du -sb {$userHome}/Maildir");
            if ($result->successful()) {
                $stats['total_size'] = intval(explode("\t", $result->output())[0]);
            }
        }

        return $stats;
    }

    /**
     * Setup email forwarding
     */
    public function setupForwarding(string $fromEmail, string $toEmail): bool
    {
        try {
            $aliasFile = "{$this->postfixConfigPath}/virtual_alias";
            file_put_contents($aliasFile, "{$fromEmail} {$toEmail}\n", FILE_APPEND);
            Process::run("postmap {$aliasFile}");
            Process::run("systemctl reload postfix");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Setup autoresponder
     */
    public function setupAutoResponder(string $email, string $message, bool $enabled = true): bool
    {
        try {
            $username = str_replace('@', '_', $email);
            $domain = explode('@', $email)[1];
            $userHome = "{$this->mailPath}/{$domain}/{$username}";
            
            if ($enabled) {
                file_put_contents("{$userHome}/.vacation.msg", $message);
                file_put_contents("{$userHome}/.vacation.enabled", '1');
            } else {
                unlink("{$userHome}/.vacation.enabled");
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
