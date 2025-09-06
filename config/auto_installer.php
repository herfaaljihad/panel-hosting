<?php

return [
    'categories' => [
        'cms' => 'Content Management',
        'ecommerce' => 'E-Commerce',
        'forum' => 'Forums',
        'blog' => 'Blogs',
        'framework' => 'Frameworks',
        'tool' => 'Tools & Utilities',
        'social' => 'Social Networks',
        'gallery' => 'Galleries'
    ],

    'apps' => [
        [
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'category' => 'cms',
            'version' => '6.6.1',
            'description' => 'WordPress is a free and open-source content management system written in PHP and paired with a MySQL or MariaDB database.',
            'icon' => 'fab fa-wordpress',
            'screenshot' => '/images/apps/wordpress.png',
            'requires_database' => true,
            'min_php_version' => '7.4',
            'size' => '65 MB',
            'popularity' => 95,
            'download_url' => 'https://wordpress.org/latest.zip',
            'demo_url' => 'https://wordpress.org/',
            'features' => [
                'User-friendly admin interface',
                'Thousands of themes and plugins',
                'SEO optimized',
                'Multi-user support',
                'Mobile responsive themes'
            ]
        ],
        [
            'name' => 'Joomla',
            'slug' => 'joomla',
            'category' => 'cms',
            'version' => '5.1.4',
            'description' => 'Joomla is a free and open-source content management system for publishing web content.',
            'icon' => 'fab fa-joomla',
            'screenshot' => '/images/apps/joomla.png',
            'requires_database' => true,
            'min_php_version' => '8.1',
            'size' => '45 MB',
            'popularity' => 75,
            'download_url' => 'https://downloads.joomla.org/cms/joomla5/5-1-4/joomla_5-1-4-stable-full_package-zip',
            'demo_url' => 'https://www.joomla.org/',
            'features' => [
                'Multilingual support',
                'Advanced user management',
                'Flexible content management',
                'Built-in SEO features',
                'Template system'
            ]
        ],
        [
            'name' => 'Drupal',
            'slug' => 'drupal',
            'category' => 'cms',
            'version' => '10.3.2',
            'description' => 'Drupal is a free and open-source web content management framework written in PHP.',
            'icon' => 'fab fa-drupal',
            'screenshot' => '/images/apps/drupal.png',
            'requires_database' => true,
            'min_php_version' => '8.1',
            'size' => '85 MB',
            'popularity' => 65,
            'download_url' => 'https://www.drupal.org/download-latest/zip',
            'demo_url' => 'https://www.drupal.org/',
            'features' => [
                'Powerful taxonomy system',
                'Advanced caching',
                'Security-focused',
                'Scalable architecture',
                'API-first approach'
            ]
        ],
        [
            'name' => 'Magento',
            'slug' => 'magento',
            'category' => 'ecommerce',
            'version' => '2.4.7',
            'description' => 'Magento is an open-source e-commerce platform written in PHP.',
            'icon' => 'fab fa-magento',
            'screenshot' => '/images/apps/magento.png',
            'requires_database' => true,
            'min_php_version' => '8.1',
            'size' => '350 MB',
            'popularity' => 80,
            'download_url' => 'https://magento.com/tech-resources/download',
            'demo_url' => 'https://magento.com/',
            'features' => [
                'Multi-store management',
                'Advanced inventory management',
                'B2B and B2C support',
                'Mobile commerce',
                'SEO optimization'
            ]
        ],
        [
            'name' => 'PrestaShop',
            'slug' => 'prestashop',
            'category' => 'ecommerce',
            'version' => '8.1.7',
            'description' => 'PrestaShop is a freemium, open-source e-commerce solution.',
            'icon' => 'fas fa-shopping-cart',
            'screenshot' => '/images/apps/prestashop.png',
            'requires_database' => true,
            'min_php_version' => '7.4',
            'size' => '120 MB',
            'popularity' => 70,
            'download_url' => 'https://www.prestashop.com/en/download',
            'demo_url' => 'https://www.prestashop.com/',
            'features' => [
                'Easy product management',
                'Multiple payment methods',
                'Shipping management',
                'Multi-language support',
                'Marketing tools'
            ]
        ],
        [
            'name' => 'OpenCart',
            'slug' => 'opencart',
            'category' => 'ecommerce',
            'version' => '4.0.2.3',
            'description' => 'OpenCart is a free open source e-commerce platform for online merchants.',
            'icon' => 'fas fa-store',
            'screenshot' => '/images/apps/opencart.png',
            'requires_database' => true,
            'min_php_version' => '7.4',
            'size' => '35 MB',
            'popularity' => 60,
            'download_url' => 'https://www.opencart.com/index.php?route=cms/download',
            'demo_url' => 'https://www.opencart.com/',
            'features' => [
                'User-friendly interface',
                'Multi-store support',
                'Payment gateway integration',
                'SEO friendly URLs',
                'Backup and restore'
            ]
        ],
        [
            'name' => 'Laravel',
            'slug' => 'laravel',
            'category' => 'framework',
            'version' => '11.0',
            'description' => 'Laravel is a web application framework with expressive, elegant syntax.',
            'icon' => 'fab fa-laravel',
            'screenshot' => '/images/apps/laravel.png',
            'requires_database' => true,
            'min_php_version' => '8.2',
            'size' => '25 MB',
            'popularity' => 90,
            'download_url' => 'https://github.com/laravel/laravel/archive/refs/heads/master.zip',
            'demo_url' => 'https://laravel.com/',
            'features' => [
                'Elegant syntax',
                'Built-in ORM (Eloquent)',
                'Artisan CLI',
                'Queue system',
                'Real-time events'
            ]
        ],
        [
            'name' => 'CodeIgniter',
            'slug' => 'codeigniter',
            'category' => 'framework',
            'version' => '4.5.4',
            'description' => 'CodeIgniter is a powerful PHP framework with a very small footprint.',
            'icon' => 'fas fa-code',
            'screenshot' => '/images/apps/codeigniter.png',
            'requires_database' => false,
            'min_php_version' => '7.4',
            'size' => '15 MB',
            'popularity' => 70,
            'download_url' => 'https://github.com/codeigniter4/CodeIgniter4/releases/latest/download/CodeIgniter4.zip',
            'demo_url' => 'https://codeigniter.com/',
            'features' => [
                'Small footprint',
                'Simple solutions',
                'Clear documentation',
                'Exceptional performance',
                'Nearly zero configuration'
            ]
        ],
        [
            'name' => 'phpMyAdmin',
            'slug' => 'phpmyadmin',
            'category' => 'tool',
            'version' => '5.2.1',
            'description' => 'phpMyAdmin is a free software tool written in PHP, intended to handle the administration of MySQL over the Web.',
            'icon' => 'fas fa-database',
            'screenshot' => '/images/apps/phpmyadmin.png',
            'requires_database' => false,
            'min_php_version' => '7.2',
            'size' => '45 MB',
            'popularity' => 85,
            'download_url' => 'https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip',
            'demo_url' => 'https://demo.phpmyadmin.net/',
            'features' => [
                'Web-based MySQL administration',
                'Database management',
                'SQL query execution',
                'Import/Export data',
                'User management'
            ]
        ],
        [
            'name' => 'MediaWiki',
            'slug' => 'mediawiki',
            'category' => 'cms',
            'version' => '1.41.1',
            'description' => 'MediaWiki is a free and open-source wiki software package written in PHP.',
            'icon' => 'fab fa-wikipedia-w',
            'screenshot' => '/images/apps/mediawiki.png',
            'requires_database' => true,
            'min_php_version' => '7.4',
            'size' => '55 MB',
            'popularity' => 75,
            'download_url' => 'https://releases.wikimedia.org/mediawiki/1.41/mediawiki-1.41.1.tar.gz',
            'demo_url' => 'https://www.mediawiki.org/',
            'features' => [
                'Wiki markup language',
                'Version control',
                'User permissions',
                'Extensions support',
                'Multi-language support'
            ]
        ]
    ]
];
