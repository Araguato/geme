<?php

return [
    'title' => 'Help center',
    'subtitle' => 'Find step-by-step guides and tips to operate geme with your team.',
    'search_placeholder' => 'Search by keyword (e.g.: "payroll", "POS", "inventory")',
    'search_hint' => 'Type at least two letters to see related articles.',
    'search_action' => 'Search',
    'search_results_title' => 'Search results',
    'search_results_none' => 'No articles matched your search.',
    'quick_links_title' => 'Quick links',
    'quick_links' => [
        [
            'label' => 'Getting started checklist',
            'description' => 'Configure business details, taxes and users in a few minutes.',
            'category' => 'general',
            'article' => 'primeros-pasos',
        ],
        [
            'label' => 'Load products and categories',
            'description' => 'Organise your catalogue and control stock levels.',
            'category' => 'inventario',
            'article' => 'catalogo-productos',
        ],
        [
            'label' => 'Charge with Fast POS',
            'description' => 'Guide to sell, handle payments and close the shift.',
            'category' => 'tpv',
            'article' => 'flujo-caja',
        ],
        [
            'label' => 'Process payroll',
            'description' => 'Generate periods, runs and adjust employee entries.',
            'category' => 'nomina',
            'article' => 'ciclo-nomina',
        ],
    ],
    'topics' => [
        'general' => [
            'title' => 'Getting started',
            'summary' => 'Configure your business, users and key parameters before operating.',
            'articles' => [
                'primeros-pasos' => [
                    'title' => 'Initial system checklist',
                    'summary' => 'Recommended tasks to set geme up from scratch.',
                    'estimated_time' => '5 minutes',
                    'content' => <<<'HTML'
<p>Complete this checklist to ensure the platform is ready before selling:</p>
<ol>
    <li><strong>Business data:</strong> go to <em>Settings &gt; Appearance</em> and upload logo, company name and default currency.</li>
    <li><strong>Taxes:</strong> in <em>Settings &gt; BCV rate</em> define the VAT or fiscal rate used for your products.</li>
    <li><strong>Users and roles:</strong> create accounts for admins, cashiers and waiters from <em>Administration &gt; Users</em>. Assign roles to control permissions.</li>
    <li><strong>Cash shifts:</strong> if you will use Fast POS, open the register in <em>Cash / Shift</em> before recording sales.</li>
    <li><strong>Initial catalogue:</strong> create at least one category and some products to test the sales flow.</li>
</ol>
<p>Once finished, run a test sale in Fast POS to validate taxes, printing and shift closing.</p>
HTML,
                    'tags' => ['configuration', 'users', 'license'],
                ],
                'roles-permisos' => [
                    'title' => 'Recommended roles & permissions',
                    'summary' => 'Define access profiles to protect sensitive operations.',
                    'estimated_time' => '4 minutes',
                    'content' => <<<'HTML'
<p>geme includes predefined roles you can assign to each user:</p>
<ul>
    <li><strong>Administrator:</strong> full access to settings, reports and finances.</li>
    <li><strong>Cashier:</strong> access to Fast POS, cash management, orders and daily reports.</li>
    <li><strong>Waiter / Dispatcher:</strong> can view tables, orders and kitchen/bar depending on enabled modules.</li>
    <li><strong>Client:</strong> designed for the online ordering portal with limited access.</li>
</ul>
<p>Avoid sharing user accounts and enable PIN login for cashiers from <em>Operator &gt; PIN</em> to register who handled each sale.</p>
HTML,
                    'tags' => ['users', 'security'],
                ],
            ],
        ],
        'inventario' => [
            'title' => 'Inventory & catalogue',
            'summary' => 'Organise categories, products and recipes to control stock.',
            'articles' => [
                'catalogo-productos' => [
                    'title' => 'Create categories and products',
                    'summary' => 'Structure your menu with images, prices and taxes.',
                    'estimated_time' => '6 minutes',
                    'content' => <<<'HTML'
<p>Follow these steps to prepare your catalogue:</p>
<ol>
    <li>Create categories in <em>Inventory &gt; Categories</em>. Use the "Active" option only for those visible in Fast POS and online menu.</li>
    <li>Configure products from <em>Inventory &gt; Products</em> including price, tax and whether it tracks stock.</li>
    <li>Add barcodes (optional) and recipes if the product consumes ingredients from inventory.</li>
    <li>Check the public menu at <code>{{ url('/menu') }}</code> to confirm images and texts.</li>
</ol>
<p>Remember to deactivate categories or products that must not appear in POS; the system will hide inactive ones automatically.</p>
HTML,
                    'tags' => ['inventory', 'products'],
                ],
                'ajustes-inventario' => [
                    'title' => 'Stock control and adjustments',
                    'summary' => 'Record entries, withdrawals and Kardex movements.',
                    'estimated_time' => '5 minutes',
                    'content' => <<<'HTML'
<p>To keep accurate stock levels:</p>
<ul>
    <li>Check current stock in <em>Inventory &gt; Stock</em> and filter by category or product.</li>
    <li>Log manual adjustments from <em>Inventory &gt; Adjustments</em> specifying reason and responsible user.</li>
    <li>Review the movement history (Kardex) for each product and validate automatic consumptions from sales.</li>
</ul>
<p>Adjustments are audited and can be exported for accounting reconciliations.</p>
HTML,
                    'tags' => ['inventory', 'kardex'],
                ],
            ],
        ],
        'tpv' => [
            'title' => 'Fast POS & cash',
            'summary' => 'Learn how to use the point of sale, payment methods and X/Z reports.',
            'articles' => [
                'flujo-caja' => [
                    'title' => 'Complete Fast POS workflow',
                    'summary' => 'Identify operator, add products, charge and close the shift.',
                    'estimated_time' => '7 minutes',
                    'content' => <<<'HTML'
<p>Suggested flow for the cashier:</p>
<ol>
    <li><strong>Open cash register:</strong> from <em>Cash / Shift</em> record initial amount and responsible operator.</li>
    <li><strong>Identify operator:</strong> in Fast POS click <em>Identify</em> and enter the assigned PIN.</li>
    <li><strong>Add products:</strong> search by name, category or scan the barcode.</li>
    <li><strong>Charge:</strong> choose the payment method (cash, card, PagoMóvil, PIX) and enter reference if required.</li>
    <li><strong>Print or send receipt:</strong> generate the ticket and hand it to the customer.</li>
    <li><strong>Close shift:</strong> at the end of the day issue an X/Z report to compare expected vs. actual amounts.</li>
</ol>
<p>If the operator forgets to close the cash drawer, an administrator can close it from the shift module.</p>
HTML,
                    'tags' => ['pos', 'cash'],
                ],
                'escaneo-codigos' => [
                    'title' => 'Enable camera scanning and barcodes',
                    'summary' => 'Requirements and tips to use built-in browser readers.',
                    'estimated_time' => '3 minutes',
                    'content' => <<<'HTML'
<p>To use camera-based scanning:</p>
<ul>
    <li>Use compatible browsers (Chrome, Edge or Safari) and grant camera permissions.</li>
    <li>Activate the <em>Scan</em> button in Fast POS; if supported, it will show on screen.</li>
    <li>Keep good lighting and place the code 10-15 cm from the camera for best results.</li>
</ul>
<p>You can also register alternate barcodes per product from <em>Inventory &gt; Products &gt; Codes</em>.</p>
HTML,
                    'tags' => ['pos', 'barcodes'],
                ],
            ],
        ],
        'nomina' => [
            'title' => 'Payroll',
            'summary' => 'Manage periods, runs and entries with manual adjustments.',
            'articles' => [
                'ciclo-nomina' => [
                    'title' => 'Complete payroll cycle',
                    'summary' => 'Create periods, run payroll and generate receipts.',
                    'estimated_time' => '8 minutes',
                    'content' => <<<'HTML'
<p>For each payroll period:</p>
<ol>
    <li><strong>Create period:</strong> define date range, type (weekly, biweekly) and set initial status to <em>Open</em>.</li>
    <li><strong>Create run:</strong> within the period, generate the run and press <em>Generate entries</em> to calculate automatic concepts.</li>
    <li><strong>Review and adjust:</strong> open each employee entry to add or edit manual concepts (bonuses, deductions, overtime).</li>
    <li><strong>Approve:</strong> when everything looks correct, approve the run to lock further changes.</li>
    <li><strong>Issue receipts:</strong> export or print receipts from the run view to share with staff.</li>
</ol>
<p>If you need to correct amounts after approval, duplicate the run or open an adjustment run to preserve traceability.</p>
HTML,
                    'tags' => ['payroll', 'hr'],
                ],
                'conceptos-manuales' => [
                    'title' => 'Add manual concepts to an entry',
                    'summary' => 'Include bonuses, deductions and specific overtime.',
                    'estimated_time' => '4 minutes',
                    'content' => <<<'HTML'
<p>From the entry edit screen:</p>
<ul>
    <li>Find the "Manual adjustments" section and click <em>Add row</em>.</li>
    <li>Select the concept or enter a free-form description, quantity, rate and amount.</li>
    <li>Mark the checkbox to subtract (deduction) or leave unchecked to add to net pay.</li>
    <li>Save changes to recalculate totals and reflect the adjustment in the payslip.</li>
</ul>
<p>Remember that an approved run cannot be modified; apply adjustments before finalising.</p>
HTML,
                    'tags' => ['payroll', 'adjustments'],
                ],
            ],
        ],
    ],
];
