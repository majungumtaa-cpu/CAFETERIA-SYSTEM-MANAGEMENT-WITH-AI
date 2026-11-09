// ============================================
// lang.js — Shared Language System
// Cafeteria Management System
// ============================================

(function() {
    'use strict';

    const CAFE_TRANSLATIONS = {
        en: {
            // ============================================
            // LOGIN
            // ============================================
            login_title: 'Login',
            login_email: 'Email Address',
            login_password: 'Password',
            login_button: 'Login',
            login_forgot: 'Forgot Password?',
            login_back: 'Back to Home',
            login_customer: 'Customer Login',
            login_staff: 'Staff Login',
            login_admin: 'Admin Login',
            login_new_customer: 'New customer?',
            login_register_here: 'Register here',
            login_username: 'Username',
            login_full_name: 'Full Name',
            login_phone: 'Phone Number',
            login_as_admin: 'Login as Admin',
            login_as_employee: 'Login as Employee',

            // ============================================
            // STAFF PORTAL
            // ============================================
            staff_portal: 'Staff Portal',
            staff_subtitle: 'Admin & Employee Login',
            staff_admin_tab: 'Admin',
            staff_employee_tab: 'Employee',

            // ============================================
            // FORGOT PASSWORD
            // ============================================
            fp_title: 'Admin Forgot Password',
            fp_subtitle: 'Enter your admin email to receive an OTP',
            fp_admin_email: 'Admin Email',
            fp_enter_email: 'Enter your admin email',
            fp_send_otp: 'Send OTP',
            fp_verify_otp: 'Verify OTP',
            fp_back_login: 'Back to Admin Login',

            // ============================================
            // RESET PASSWORD
            // ============================================
            rp_title: 'Reset Password',
            rp_success_title: 'Password Reset!',
            rp_success_subtitle: 'You can now log in with your new password',
            rp_set_new: 'Set New Password',
            rp_subtitle: 'Choose a new password for your account',
            rp_go_login: 'Go to Login',
            rp_new_password: 'New Password',
            rp_new_password_ph: 'At least 6 characters',
            rp_confirm_password: 'Confirm Password',
            rp_confirm_password_ph: 'Re-enter password',
            rp_reset_btn: 'Reset Password',
            rp_request_otp: 'Request New OTP',

            // ============================================
            // REGISTER
            // ============================================
            reg_title: 'Customer Registration',
            reg_go_login: 'Go to Login',
            reg_full_name: 'Full Name',
            reg_full_name_ph: 'e.g., John Doe',
            reg_phone: 'Phone Number',
            reg_phone_ph: '+255 712 345 678',
            reg_email: 'Email Address',
            reg_email_ph: 'you@example.com',
            reg_password: 'Password',
            reg_password_ph: 'At least 6 characters',
            reg_password_hint: 'Minimum 6 characters',
            reg_confirm_password: 'Confirm Password',
            reg_confirm_password_ph: 'Re-enter your password',
            reg_button: 'Create Account',
            reg_have_account: 'Already have an account?',
            reg_login_here: 'Login here',

            // ============================================
            // COMMON
            // ============================================
            dashboard: 'Dashboard',
            welcome: 'Welcome',
            menu: 'Menu',
            orders: 'Orders',
            cart: 'Cart',
            profile: 'Profile',
            logout: 'Logout',
            settings: 'Settings',
            notifications: 'Notifications',
            search: 'Search',
            products: 'Products',
            customers: 'Customers',
            employees: 'Employees',
            reports: 'Reports',
            sales: 'Sales',
            payments: 'Payments',
            reservations: 'Reservations',
            feedback: 'Feedback',
            inventory: 'Inventory',
            categories: 'Categories',
            expenses: 'Expenses',
            events: 'Events',
            suppliers: 'Suppliers',
            shifts: 'Shifts',
            payment_methods: 'Payment Methods',

            // ============================================
            // ACTIONS
            // ============================================
            add: 'Add',
            edit: 'Edit',
            delete: 'Delete',
            save: 'Save',
            cancel: 'Cancel',
            confirm: 'Confirm',
            update: 'Update',
            view: 'View',
            close: 'Close',
            submit: 'Submit',
            reset: 'Reset',
            clear: 'Clear',
            back: 'Back',
            yes: 'Yes',
            no: 'No',
            ok: 'OK',

            // ============================================
            // TABLE LABELS
            // ============================================
            status: 'Status',
            date: 'Date',
            time: 'Time',
            total: 'Total',
            subtotal: 'Subtotal',
            quantity: 'Quantity',
            price: 'Price',
            amount: 'Amount',
            actions: 'Actions',
            name: 'Name',
            email: 'Email',
            phone: 'Phone',
            address: 'Address',
            description: 'Description',
            category: 'Category',
            image: 'Image',
            notes: 'Notes',
            id: 'ID',
            pending: 'Pending',
            processing: 'Processing',
            completed: 'Completed',
            cancelled: 'Cancelled',
            confirmed: 'Confirmed',
            ready: 'Ready',
            active: 'Active',
            inactive: 'Inactive',

            // ============================================
            // CHATBOT
            // ============================================
            askAnything: 'Ask me anything!',
            newChat: 'New Chat',
            prevChats: 'Previous Chats',
            clearAll: 'Clear All',
            assistantName: 'Cafeteria Assistant',
            online: 'Online',
            typePlaceholder: 'Type or speak...',

            // ============================================
            // CUSTOMER DASHBOARD
            // ============================================
            brand: 'Cafeteria',
            welcome_back: 'Welcome back',
            what_today: 'What would you like to do today?',
            total_orders: 'Total Orders',
            total_spent: 'Total Spent',
            cart_items: 'Items in Cart',
            order_food: 'Order Food',
            browse_menu: 'Browse our Menu',
            my_cart: 'My Cart',
            items: 'item(s)',
            upcoming_events: 'Upcoming events',
            share_experience: 'Share experience',
            recent_orders: 'Recent Orders',
            order: 'Order',
            no_orders: 'No recent orders found.',
            upcoming_reservations: 'Upcoming Reservations',
            registered_events: 'Registered Events'
        },

        sw: {
            // ============================================
            // LOGIN
            // ============================================
            login_title: 'Ingia',
            login_email: 'Barua Pepe',
            login_password: 'Neno la Siri',
            login_button: 'Ingia',
            login_forgot: 'Umesahau Neno la Siri?',
            login_back: 'Rudi Nyumbani',
            login_customer: 'Ingia kama Mteja',
            login_staff: 'Ingia kama Mfanyakazi',
            login_admin: 'Ingia kama Msimamizi',
            login_new_customer: 'Mteja mpya?',
            login_register_here: 'Sajili hapa',
            login_username: 'Jina la Mtumiaji',
            login_full_name: 'Jina Kamili',
            login_phone: 'Namba ya Simu',
            login_as_admin: 'Ingia kama Msimamizi',
            login_as_employee: 'Ingia kama Mfanyakazi',

            // ============================================
            // STAFF PORTAL
            // ============================================
            staff_portal: 'Lango la Wafanyakazi',
            staff_subtitle: 'Ingia kama Msimamizi au Mfanyakazi',
            staff_admin_tab: 'Msimamizi',
            staff_employee_tab: 'Mfanyakazi',

            // ============================================
            // FORGOT PASSWORD
            // ============================================
            fp_title: 'Kusahau Neno la Siri',
            fp_subtitle: 'Weka barua pepe yako kupokea OTP',
            fp_admin_email: 'Barua Pepe',
            fp_enter_email: 'Weka barua pepe yako',
            fp_send_otp: 'Tuma OTP',
            fp_verify_otp: 'Thibitisha OTP',
            fp_back_login: 'Rudi kwenye Ingia',

            // ============================================
            // RESET PASSWORD
            // ============================================
            rp_title: 'Badilisha Neno la Siri',
            rp_success_title: 'Neno la Siri Limebadilishwa!',
            rp_success_subtitle: 'Unaweza kuingia sasa',
            rp_set_new: 'Weka Neno la Siri Jipya',
            rp_subtitle: 'Chagua neno jipya la siri',
            rp_go_login: 'Nenda kwenye Ingia',
            rp_new_password: 'Neno la Siri Jipya',
            rp_new_password_ph: 'Herufi 6 au zaidi',
            rp_confirm_password: 'Thibitisha Neno la Siri',
            rp_confirm_password_ph: 'Weka tena neno la siri',
            rp_reset_btn: 'Badilisha Neno la Siri',
            rp_request_otp: 'Omba OTP Mpya',

            // ============================================
            // REGISTER
            // ============================================
            reg_title: 'Usajili wa Mteja',
            reg_go_login: 'Nenda kwenye Ingia',
            reg_full_name: 'Jina Kamili',
            reg_full_name_ph: 'mfano, John Doe',
            reg_phone: 'Namba ya Simu',
            reg_phone_ph: '+255 712 345 678',
            reg_email: 'Barua Pepe',
            reg_email_ph: 'wewe@example.com',
            reg_password: 'Neno la Siri',
            reg_password_ph: 'Herufi 6 au zaidi',
            reg_password_hint: 'Kiwango cha chini: herufi 6',
            reg_confirm_password: 'Thibitisha Neno la Siri',
            reg_confirm_password_ph: 'Weka tena neno la siri',
            reg_button: 'Unda Akaunti',
            reg_have_account: 'Tayari una akaunti?',
            reg_login_here: 'Ingia hapa',

            // ============================================
            // COMMON
            // ============================================
            dashboard: 'Dashibodi',
            welcome: 'Karibu',
            menu: 'Menyu',
            orders: 'Oda',
            cart: 'Kikapu',
            profile: 'Wasifu',
            logout: 'Toka',
            settings: 'Mipangilio',
            notifications: 'Taarifa',
            search: 'Tafuta',
            products: 'Bidhaa',
            customers: 'Wateja',
            employees: 'Wafanyakazi',
            reports: 'Ripoti',
            sales: 'Mauzo',
            payments: 'Malipo',
            reservations: 'Uhifadhi',
            feedback: 'Maoni',
            inventory: 'Bidhaa Zilizopo',
            categories: 'Kategoria',
            expenses: 'Matumizi',
            events: 'Matukio',
            suppliers: 'Wasambazaji',
            shifts: 'Zamu',
            payment_methods: 'Njia za Malipo',

            // ============================================
            // ACTIONS
            // ============================================
            add: 'Ongeza',
            edit: 'Badilisha',
            delete: 'Futa',
            save: 'Hifadhi',
            cancel: 'Ghairi',
            confirm: 'Thibitisha',
            update: 'Sasisha',
            view: 'Ona',
            close: 'Funga',
            submit: 'Tuma',
            reset: 'Weka Upya',
            clear: 'Safisha',
            back: 'Rudi',
            yes: 'Ndio',
            no: 'Hapana',
            ok: 'Sawa',

            // ============================================
            // TABLE LABELS
            // ============================================
            status: 'Hali',
            date: 'Tarehe',
            time: 'Muda',
            total: 'Jumla',
            subtotal: 'Jumla Ndogo',
            quantity: 'Kiasi',
            price: 'Bei',
            amount: 'Kiasi',
            actions: 'Vitendo',
            name: 'Jina',
            email: 'Barua Pepe',
            phone: 'Simu',
            address: 'Anwani',
            description: 'Maelezo',
            category: 'Kategoria',
            image: 'Picha',
            notes: 'Maelezo',
            id: 'Kitambulisho',
            pending: 'Inasubiri',
            processing: 'Inashughulikiwa',
            completed: 'Imekamilika',
            cancelled: 'Imefutwa',
            confirmed: 'Imethibitishwa',
            ready: 'Iko Tayari',
            active: 'Inatumika',
            inactive: 'Haifanyi Kazi',

            // ============================================
            // CHATBOT
            // ============================================
            askAnything: 'Niulize kitu chochote!',
            newChat: 'Mazungumzo Mpya',
            prevChats: 'Mazungumzo ya Nyuma',
            clearAll: 'Futa Zote',
            assistantName: 'Msaidizi wa Cafeteria',
            online: 'Mtandaoni',
            typePlaceholder: 'Andika au ongea...',

            // ============================================
            // CUSTOMER DASHBOARD
            // ============================================
            brand: 'Cafeteria',
            welcome_back: 'Karibu tena',
            what_today: 'Ungependa kufanya nini leo?',
            total_orders: 'Jumla ya Oda',
            total_spent: 'Jumla Iliyotumika',
            cart_items: 'Bidhaa kwenye Kikapu',
            order_food: 'Agiza Chakula',
            browse_menu: 'Angalia Menyu Yetu',
            my_cart: 'Kikapu Changu',
            items: 'bidhaa',
            upcoming_events: 'Matukio Yajayo',
            share_experience: 'Shiriki uzoefu',
            recent_orders: 'Oda za Hivi Karibuni',
            order: 'Oda',
            no_orders: 'Hakuna oda za hivi karibuni.',
            upcoming_reservations: 'Uhifadhi Ujao',
            registered_events: 'Matukio Uliyosajiliwa'
        }
    };

    // ============================================
    // CORE FUNCTIONS
    // ============================================

    function getCurrentLang() {
        try {
            return localStorage.getItem('cafeteria_lang') || 'en';
        } catch (e) {
            return 'en';
        }
    }

    function t(key) {
        const lang = getCurrentLang();
        return (CAFE_TRANSLATIONS[lang] && CAFE_TRANSLATIONS[lang][key])
            || CAFE_TRANSLATIONS.en[key]
            || key;
    }

    function setLanguage(lang) {
        if (!CAFE_TRANSLATIONS[lang]) lang = 'en';

        try {
            localStorage.setItem('cafeteria_lang', lang);
        } catch (e) {}

        document.documentElement.lang = lang;

        try {
            const path = window.location.pathname;
            let setLangPath = 'set_lang.php';
            if (path.includes('/user/') || path.includes('/admin/') || path.includes('/employee/')) {
                setLangPath = '../set_lang.php';
            }
            fetch(setLangPath + '?lang=' + lang, { method: 'GET' }).catch(function() {});
        } catch (e) {}

        applyTranslations();

        document.querySelectorAll('.lang-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.lang === lang);
        });

        window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang: lang } }));
    }

    function applyTranslations() {
        document.querySelectorAll('[data-i18n]').forEach(function(el) {
            const key = el.getAttribute('data-i18n');
            const translated = t(key);

            if (el.hasAttribute('data-i18n-placeholder')) {
                el.placeholder = translated;
            } else if (el.hasAttribute('data-i18n-title')) {
                el.title = translated;
            } else if (el.hasAttribute('data-i18n-value')) {
                el.value = translated;
            } else {
                const icon = el.querySelector('i');
                if (icon) {
                    let hasText = false;
                    el.childNodes.forEach(function(node) {
                        if (node.nodeType === 3 && node.textContent.trim() !== '') {
                            node.textContent = ' ' + translated;
                            hasText = true;
                        }
                    });
                    if (!hasText) {
                        el.appendChild(document.createTextNode(' ' + translated));
                    }
                } else {
                    el.textContent = translated;
                }
            }
        });

        const titleEl = document.querySelector('title[data-i18n]');
        if (titleEl) {
            document.title = t(titleEl.getAttribute('data-i18n'));
        }
    }

    function injectLangSwitcher() {
        if (document.getElementById('lang-switcher')) return;
        if (document.querySelector('.top-bar')) return;

        const div = document.createElement('div');
        div.id = 'lang-switcher';
        div.innerHTML = '<button class="lang-btn" data-lang="en" onclick="setLanguage(\'en\')">EN</button>'
                      + '<button class="lang-btn" data-lang="sw" onclick="setLanguage(\'sw\')">SW</button>';
        document.body.appendChild(div);

        const current = getCurrentLang();
        div.querySelectorAll('.lang-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.lang === current);
        });
    }

    function injectLangCSS() {
        if (document.getElementById('lang-switcher-css')) return;
        const style = document.createElement('style');
        style.id = 'lang-switcher-css';
        style.textContent = [
            '#lang-switcher {',
            '    position: fixed;',
            '    top: 15px;',
            '    right: 15px;',
            '    z-index: 9998;',
            '    display: flex;',
            '    gap: 4px;',
            '    background: rgba(74, 46, 31, 0.92);',
            '    padding: 4px;',
            '    border-radius: 12px;',
            '    box-shadow: 0 6px 20px rgba(0,0,0,0.3);',
            '    backdrop-filter: blur(10px);',
            '    font-family: Segoe UI, Tahoma, sans-serif;',
            '}',
            '#lang-switcher .lang-btn {',
            '    background: transparent;',
            '    color: rgba(255,255,255,0.7);',
            '    border: none;',
            '    padding: 7px 13px;',
            '    border-radius: 8px;',
            '    font-size: 0.75em;',
            '    font-weight: 700;',
            '    cursor: pointer;',
            '    transition: all 0.2s;',
            '    font-family: inherit;',
            '}',
            '#lang-switcher .lang-btn:hover {',
            '    color: white;',
            '    background: rgba(255,255,255,0.1);',
            '}',
            '#lang-switcher .lang-btn.active {',
            '    background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);',
            '    color: white;',
            '}',
            '@media (max-width: 768px) {',
            '    #lang-switcher { top: 10px; right: 10px; }',
            '    #lang-switcher .lang-btn { padding: 6px 10px; font-size: 0.7em; }',
            '}'
        ].join('\n');
        document.head.appendChild(style);
    }

    // Expose globally
    window.getCurrentLang = getCurrentLang;
    window.t = t;
    window.setLanguage = setLanguage;
    window.applyTranslations = applyTranslations;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            try {
                injectLangCSS();
                injectLangSwitcher();
                applyTranslations();
                document.documentElement.lang = getCurrentLang();
            } catch (e) {
                console.warn('lang.js init error:', e);
            }
        });
    } else {
        try {
            injectLangCSS();
            injectLangSwitcher();
            applyTranslations();
            document.documentElement.lang = getCurrentLang();
        } catch (e) {
            console.warn('lang.js init error:', e);
        }
    }
})();