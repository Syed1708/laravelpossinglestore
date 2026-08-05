<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard routes prefix and middleware.
    |
    */
    'routes' => [
        'prefix' => env('TYRO_DASHBOARD_PREFIX', 'dashboard'),
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'tyro-dashboard.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Roles
    |--------------------------------------------------------------------------
    |
    | Users with these roles will have full access to admin features
    | (user management, role management, privilege management, settings).
    |
    */
    'admin_roles' => ['admin', 'super-admin'],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model to use throughout the dashboard.
    |
    */
    'user_model' => env('TYRO_DASHBOARD_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for lists.
    |
    */
    'pagination' => [
        'users' => 15,
        'roles' => 15,
        'privileges' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Customize the dashboard appearance.
    |
    */
    'branding' => [
        'app_name' => env('TYRO_DASHBOARD_APP_NAME', env('APP_NAME', 'Laravel')),
        'logo' => env('TYRO_DASHBOARD_LOGO', null),
        'logo_height' => env('TYRO_DASHBOARD_LOGO_HEIGHT', '32px'),
        'favicon' => env('TYRO_DASHBOARD_FAVICON', null),

        // Sidebar colors (supports any CSS color value: hex, rgb, hsl, etc.)
        'sidebar_bg' => env('TYRO_DASHBOARD_SIDEBAR_BG', null), // Custom background color for sidebar
        'sidebar_text' => env('TYRO_DASHBOARD_SIDEBAR_TEXT', null), // Custom text color for sidebar
        'sidebar_primary' => env('TYRO_DASHBOARD_SIDEBAR_PRIMARY', null), // Custom text color for sidebar
        'sidebar_accent' => env('TYRO_DASHBOARD_SIDEBAR_ACCENT', null), // Custom text color for sidebar
        'sidebar_accent_foreground' => env('TYRO_DASHBOARD_SIDEBAR_ACCENT_FOREGROUND', null), // Custom text color for sidebar
        'sidebar_header_border' => env('TYRO_DASHBOARD_SIDEBAR_HEADER_BORDER', null), // Custom text color for sidebar
        'sidebar_accordion_compact' => filter_var(env('TYRO_DASHBOARD_SIDEBAR_ACCORDION_COMPACT', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Bar
    |--------------------------------------------------------------------------
    |
    | Configuration for the admin notice bar displayed at the top of the dashboard.
    |
    */
    'admin_bar' => [
        'enabled' => env('TYRO_DASHBOARD_ADMIN_BAR_ENABLED', false),
        'message' => env('TYRO_DASHBOARD_ADMIN_BAR_MESSAGE', 'This is a sample admin message.'),
        'bg_color' => env('TYRO_DASHBOARD_ADMIN_BAR_BG_COLOR', '#000000'),
        'text_color' => env('TYRO_DASHBOARD_ADMIN_BAR_TEXT_COLOR', '#ffffff'),
        'align' => env('TYRO_DASHBOARD_ADMIN_BAR_ALIGN', 'left'),
        'height' => env('TYRO_DASHBOARD_ADMIN_BAR_HEIGHT', '40px'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Collapsible Sidebar
    |--------------------------------------------------------------------------
    |
    | Enable or disable the collapsible sidebar feature.
    |
    */
    'collapsible_sidebar' => env('TYRO_DASHBOARD_COLLAPSIBLE_SIDEBAR', true),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific dashboard features.
    |
    */
    'features' => [
        'user_management' => true,
        'role_management' => true,
        'privilege_management' => true,
        'settings_management' => true,
        'profile_management' => true,
        'invitation_system' => env('TYRO_DASHBOARD_ENABLE_INVITATION', false),
        'audit_logs' => env('TYRO_DASHBOARD_ENABLE_AUDIT_LOGS', false),
        'activity_log' => false, // Future feature
        'profile_photo_upload' => env('TYRO_DASHBOARD_ENABLE_PROFILE_PHOTO', false),
        'gravatar' => env('TYRO_DASHBOARD_ENABLE_GRAVATAR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Resources
    |--------------------------------------------------------------------------
    |
    | Resources that cannot be deleted through the dashboard.
    |
    */
    'protected' => [
        'roles' => ['admin', 'super-admin', 'user'],
        'users' => [], // Add user IDs that cannot be deleted
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Configure which widgets appear on the dashboard home.
    |
    */
    'widgets' => [
        'stats' => true,
        'recent_users' => true,
        'role_distribution' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure dashboard notifications behavior.
    |
    */
    'notifications' => [
        'show_flash_messages' => true,
        'auto_dismiss_seconds' => 5,
        'notification_style' => env('TYRO_DASHBOARD_NOTIFICATION_STYLE', 'legacy'), // 'legacy' or 'toast'
        'toast_position' => env('TYRO_DASHBOARD_TOAST_POSITION', 'bottom-right'), // 'top-right' or 'bottom-right'
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default settings for file uploads in resources.
    |
    */
    'uploads' => [
        'disk' => env('TYRO_DASHBOARD_UPLOAD_DISK', 'public'),
        'directory' => env('TYRO_DASHBOARD_UPLOAD_DIRECTORY', 'uploads'),
        'auto_delete_on_resource_delete' => env('TYRO_DASHBOARD_AUTO_DELETE_UPLOADS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Photo Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings for user profile photos and gravatar support.
    |
    */
    'profile_photo' => [
        'disk' => env('TYRO_DASHBOARD_PROFILE_PHOTO_DISK', 'public'),
        'directory' => env('TYRO_DASHBOARD_PROFILE_PHOTO_DIRECTORY', 'profile_images'),
        'max_size' => env('TYRO_DASHBOARD_PROFILE_PHOTO_MAX_SIZE', 10240), // in KB (default 10MB)
        'width' => env('TYRO_DASHBOARD_PROFILE_PHOTO_WIDTH', 400),
        'height' => env('TYRO_DASHBOARD_PROFILE_PHOTO_HEIGHT', 400),
        'quality' => env('TYRO_DASHBOARD_PROFILE_PHOTO_QUALITY', 90),
        'crop_position' => env('TYRO_DASHBOARD_PROFILE_PHOTO_CROP', 'center'), // top, center, bottom
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'auto_delete_on_user_delete' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Resources (CRUD)
    |--------------------------------------------------------------------------
    |
    | Define your resources here to automatically generate CRUD interfaces.
    |
    */
    'resources' => [


        // 'stores' => [
        //     'model' => 'App\Models\Store',
        //     'title' => 'Boutiques (Stores)',
        //     'roles' => ['admin', 'superadmin'], // Accessible only by admins
        //     'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 1.996-.946a3.001 3.001 0 0 0 3.75.615 2.993 2.993 0 0 0 2.25-.615 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.5a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>',
        //     'fields' => [
        //         'name' => ['type' => 'text', 'label' => 'Nom de la Boutique', 'rules' => 'required|max:255'],
        //         'address' => ['type' => 'text', 'label' => 'Adresse', 'rules' => 'nullable|max:255'],
        //         'postal_code' => ['type' => 'text', 'label' => 'Code Postal', 'rules' => 'nullable|max:10'],
        //         'city' => ['type' => 'text', 'label' => 'Ville', 'rules' => 'nullable|max:100'],
        //         'siret' => ['type' => 'text', 'label' => 'Numéro SIRET', 'rules' => 'nullable|size:14'],
        //         'vat_number' => ['type' => 'text', 'label' => 'Numéro de TVA Intracom', 'rules' => 'nullable|max:20'],
        //     ]
        // ],
        // ==========================================
        // 🚀 1. DISPLAY & POS GROUP (Custom Links)
        // ==========================================
        'web_pos' => [
            'group'  => 'Display & POS',
            'title'  => '⌨️ Web POS Terminal',
            'url'    => '/pos',
            'target' => '_blank',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.559c.211.135.442.2.673.2.226 0 .453-.064.661-.19a1.122 1.122 0 00.465-.916c0-.528-.4-.954-.925-1.042l-.4-.067c-.525-.088-.925-.514-.925-1.042 0-.376.183-.728.497-.918a1.121 1.121 0 011.077-.14l.879.56M12 3v18" /></svg>',
        ],

        'kds_chef' => [
            'group'  => 'Display & POS',
            'title'  => '👨‍🍳 Chef Screen (KDS)',
            'route'  => 'admin.kds.chef',
            'target' => '_blank',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
        ],

        'kds_packer' => [
            'group'  => 'Display & POS',
            'title'  => '📦 Cashier/Packer Screen',
            'route'  => 'admin.kds.packer',
            'target' => '_blank',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25M19.5 3H4.5A1.5 1.5 0 003 4.5v3a1.5 1.5 0 001.5 1.5h15A1.5 1.5 0 0021 7.5v-3A1.5 1.5 0 0019.5 3z" /></svg>',
        ],

        'categories' => [
            'group' => 'Customer Management',
            'model' => 'App\Models\Category',
            'title' => 'Categories',
            'roles' => ['admin', 'super-admin'],
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Category Name', 'rules' => 'required|max:255|unique:categories,name', 'searchable' => true],
            ],

        ],

        'products' => [
            'group' => 'Customer Management',
            'model' => 'App\Models\Product',
            'title' => 'Products',
            'roles' => ['admin', 'super-admin'],
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>',
            'fields' => [
                'name' => [
                    'type' => 'text',
                    'label' => 'Product Name',
                    'rules' => 'required|max:255 |unique:products,name',
                    'searchable' => true
                ],
                // 🚀 NEW: Product Description Textarea
                'description' => [
                    'type' => 'textarea',
                    'label' => 'Product Description',
                    'rules' => 'nullable|string|max:1000'
                ],

                // 🚀 NEW: Product Image Upload Field (Saved directly to public disk!)
                'image_path' => [
                    'type' => 'file',
                    'label' => 'Product Image',
                    'rules' => 'nullable|image|max:2048' // Max 2MB images
                ],
                'category_id' => [
                    'type' => 'select',
                    'label' => 'Category',
                    'relationship' => 'category',
                    'option_label' => 'name',
                    'rules' => 'required',
                ],
                'price' => ['type' => 'number', 'label' => 'Selling Price (TTC) in €', 'rules' => 'required|numeric|min:0'],
                'vat_rate' => [
                    'type' => 'select',
                    'label' => 'VAT Rate',
                    'options' => [
                        '10.00' => '10,0% (Hot Dishes / On Site)',
                        '5.50' => '5,5% (Cold Dishes / To Go / Beverages)',
                        '20.00' => '20,0% (Sodas / Alcohols)'
                    ],
                    'rules' => 'required'
                ],

                'is_active' => ['type' => 'boolean', 'label' => 'Active (Displayed on POS)', 'default' => false],
            ],



        ],
        // 🚀 3. Orders Resource (Strictly Read-Only)
        'orders' => [
            'group' => 'Manage Orders',
            'model' => 'App\Models\Order',
            'title' => 'Orders (Sales Archive)',

            // 🛡️ SECURITY: Only 'admin' and 'superadmin' can view this list,
            // and they can ONLY view it (All Create/Edit/Delete actions are hidden)
            'roles' => ['admin', 'super-admin'],
            'readonly' => ['admin', 'super-admin'],

            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
            'fields' => [
                'sequence_number' => [
                    'type' => 'text',
                    'label' => 'Ticket Number',
                ],
                'uuid' => [
                    'type' => 'text',
                    'label' => 'UUID (Unique ID)',
                ],
                'total_incl_vat' => [
                    'type' => 'number',
                    'label' => 'Total TTC (€)',
                ],
                'vat_amount' => [
                    'type' => 'number',
                    'label' => 'VAT Collected (€)',
                ],
                'subtotal_excl_vat' => [
                    'type' => 'number',
                    'label' => 'Total HT (€)',
                ],
                'completed_at' => [
                    'type' => 'datetime',
                    'label' => 'Sale Date',
                ],
            ],
        ],
        'online_orders_management' => [
            'group'  => 'Manage Orders',
            'title'  => '🔔 Online Orders',
            'route'  => 'admin.orders.online',
            'target' => '_blank',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>',
        ],

        // 🚀 4. Z-Reports Archiving Resource (View-only for everyone!)
        'daily_closures' => [
            'group' => 'Customer Management',
            'model' => 'App\Models\DailyClosure',
            'title' => 'Z-Reports (End-of-Day Closures)',

            'roles' => ['admin', 'super-admin'],
            'readonly' => ['admin', 'super-admin'],

            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18m-18 0V5.25A2.25 2.25 0 014.25 3h15.5A2.25 2.25 0 0122 5.25V13.5m-19.5 0v6.75A2.25 2.25 0 004.75 22.5h14.5a2.25 2.25 0 002.25-2.25V13.5M9 9h6M9 12h6" /></svg>',
            'fields' => [
                'z_number' => [
                    'type' => 'text',
                    'label' => 'Z-Report Number',
                ],
                'total_ttc' => [
                    'type' => 'number',
                    'label' => 'Total TTC (€)',
                ],
                'total_ht' => [
                    'type' => 'number',
                    'label' => 'Total HT (€)',
                ],
                'total_tva' => [
                    'type' => 'number',
                    'label' => 'Total TVA (€)',
                ],
                'closed_at' => [
                    'type' => 'datetime',
                    'label' => 'Closure Date',
                ],
            ],
        ],

        // 🚀 NEW: Suppliers Resource (Module 3)
        'suppliers' => [
            'group' => 'Customer Management', // 🚀 Group Header

            'model' => 'App\Models\Supplier',
            'title' => 'Suppliers',
            'roles' => ['admin', 'super-admin'],
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Supplier Name', 'rules' => 'required|max:255'],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'rules' => 'nullable|max:255'],
                'contact_name' => ['type' => 'text', 'label' => 'Contact Name', 'rules' => 'nullable|max:255'],
                'email' => ['type' => 'text', 'label' => 'Email Address', 'rules' => 'nullable|email'],
                'phone' => ['type' => 'text', 'label' => 'Phone Number', 'rules' => 'nullable|max:50'],
            ]
        ],

        'ingredients' => [
            'group' => 'Customer Management', // 🚀 Group Header
            'model' => 'App\Models\Ingredient',
            'title' => 'Ingredients (Stocks)',
            'roles' => ['admin', 'super-admin'], // Restrict access to admins only
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.83m-3.703 3.75l-5.877-5.83a2.652 2.652 0 00-3.75 3.75l5.877 5.83m3.703-3.75V11.42m0 3.75H11.42m3.75-3.75V5.62c0-1.034-.84-1.874-1.874-1.874H5.62A1.874 1.874 0 003.75 5.62v6.18c0 1.034.84 1.874 1.874 1.874h6.18c1.034 0 1.874-.84 1.874-1.874V11.42z" /></svg>',
            'fields' => [
                'name' => [
                    'type' => 'select',
                    'label' => 'Ingredient Name',
                    'options' => [
                        // 🥖 Boulangerie / Pains
                        'Pain Burger (Bun)' => '🍞 Pain Burger (Bun)',
                        'Pain Brioché' => '🍞 Pain Brioché',
                        'Galette Tortilla' => '🌯 Galette Tortilla',

                        // Boucherie / Viandes
                        'Steack Haché de Bœuf' => '🥩 Steack Haché de Bœuf',
                        'Filet de Poulet' => '🍗 Filet de Poulet',
                        'Tranches de Bacon' => '🥓 Tranches de Bacon',
                        'Knacki / Saucisse' => '🌭 Knacki / Saucisse',
                        'Poisson Pané' => '🐟 Poisson Pané (Fish)',

                        // Fromages / Crèmerie
                        'Cheddar Tranche' => '🧀 Cheddar Tranche',
                        'Emmental Tranche' => '🧀 Emmental Tranche',
                        'Mozzarella Tranche' => '🧀 Mozzarella Tranche',
                        'Chèvre Tranche' => '🧀 Chèvre Tranche',

                        // Légumes / Frais
                        'Pomme de terre (Frites)' => '🥔 Pomme de terre (Frites)',
                        'Salade Verte' => '🥬 Salade Verte',
                        'Rondelles de Tomate' => '🍅 Rondelles de Tomate',
                        'Rondelles d\'Oignon' => '🧅 Rondelles d\'Oignon',
                        'Cornichons (Pickles)' => '🥒 Cornichons (Pickles)',
                        'Avocat Frais' => '🥑 Avocat Frais',

                        // Boissons
                        'Canette Coca-Cola 33cl' => '🥤 Canette Coca-Cola 33cl',
                        'Canette Sprite 33cl' => '🥤 Canette Sprite 33cl',
                        'Canette Fanta 33cl' => '🥤 Canette Fanta 33cl',
                        'Bouteille Eau Evian 50cl' => '💧 Bouteille Eau Evian 50cl',

                        // Sauces & Divers
                        'Huile de friture' => '🛢️ Huile de friture (Litres)',
                        'Sel' => '🧂 Sel (Grammes)',
                        'Poivre' => '🧂 Poivre (Grammes)',
                        'Sauce Ketchup' => '🥫 Sauce Ketchup',
                        'Sauce Mayonnaise' => '🥫 Sauce Mayonnaise',
                        'Sauce BBQ' => '🥫 Sauce BBQ',
                    ],
                    'rules' => 'required'
                ],

                // 🚀 NEW: Relational dropdown linking your raw ingredients to their primary suppliers!
                'primary_supplier_id' => [
                    'type' => 'select',
                    'label' => 'Supplier (Primary)',
                    'relationship' => 'supplier', // References the belongsTo method in Ingredient.php
                    'option_label' => 'name',       // Display supplier name in the dropdown
                    'rules' => 'nullable',
                ],

                'stock_level' => [
                    'type' => 'number',
                    'label' => 'Current Stock Level',
                    'rules' => 'required|numeric|min:0'
                ],
                'alert_level' => [
                    'type' => 'number',
                    'label' => 'Alert Stock Level (Threshold)',
                    'rules' => 'required|numeric|min:0'
                ],
                'unit' => [
                    'type' => 'select',
                    'label' => 'Measurement Unit',
                    'options' => [
                        'unit' => 'Unit (Piece)',
                        'g' => 'Grams (g)',
                        'kg' => 'Kilograms (kg)',
                        'cl' => 'Centiliters (cl)',
                        'l' => 'Liters (l)',
                    ],
                    'rules' => 'required'
                ],
            ]
        ],

        // 🚀 9. Expenses Resource (General operating expenses ledger)
        'expenses' => [
            'model' => 'App\Models\Expense',
            'title' => 'Expenses',
            'roles' => ['admin', 'super-admin'],
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>',
            'fields' => [
                'category' => [
                    'type' => 'select',
                    'label' => 'Expense Category',
                    'options' => [
                        'food_cost' => 'Food Cost (Auto)',
                        'electricity' => 'Electricity (Public Service)',
                        'water' => 'Water (Public Service)',
                        'salaries' => 'Salaries & Personnel',
                        'rent' => 'Rent & Lease',
                        'marketing' => 'Publicity & Marketing',
                        'other' => 'Other Miscellaneous Expenses',
                    ],
                    'rules' => 'required',
                ],
                'description' => ['type' => 'text', 'label' => 'Expense Description', 'rules' => 'required|max:255'],
                'amount' => ['type' => 'number', 'label' => 'Expense Amount (€)', 'rules' => 'required|numeric|min:0.01'],
                'payment_method' => [
                    'type' => 'select',
                    'label' => 'Payment Method',
                    'options' => [
                        'bank_transfer' => 'Bank Transfer (Standard)',
                        'card' => 'Credit Card (CB)',
                        'cash' => 'Cash (Till)',
                    ],
                    'rules' => 'required',
                ],
                'due_date' => ['type' => 'datetime', 'label' => 'Due Date', 'rules' => 'nullable|date'],
                'paid_at' => ['type' => 'datetime', 'label' => 'Actual Payment Date (Leave blank if not paid)', 'rules' => 'nullable|date'],
            ]
        ],



    ],


    /*
    |--------------------------------------------------------------------------
    | Resource UI Settings
    |--------------------------------------------------------------------------
    |
    | Configure the appearance and behavior of resource forms and lists.
    |
    */
    'resource_ui' => [
        'show_global_errors' => env('TYRO_SHOW_GLOBAL_ERRORS', true),
        'show_field_errors' => env('TYRO_SHOW_FIELD_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disable Examples
    |--------------------------------------------------------------------------
    |
    | If this is true, the "Examples" section in the sidebar will be hidden
    | and the example routes will be disabled.
    |
    */
    'disable_examples' => env('TYRO_DASHBOARD_DISABLE_EXAMPLES', true),
];
