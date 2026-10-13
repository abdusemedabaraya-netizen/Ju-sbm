<!-- <section class="jiacrs-section jiacrs-section-alt" id="categories">
    <div class="container">

        <h2 class="jiacrs-section-title">Reporting Categories</h2>
        <p class="jiacrs-section-subtitle">Select a category to start your report. You can report any of these integrity-related concerns.</p>

        <div class="row g-3">
            @foreach ([
                ['slug' => 'corruption',           'title' => 'Corruption',           'icon' => 'bi-cash-coin',          'color' => 'blue'],
                ['slug' => 'bribery',              'title' => 'Bribery',              'icon' => 'bi-gift-fill',          'color' => 'green'],
                ['slug' => 'fraud',                'title' => 'Fraud',                'icon' => 'bi-shield-exclamation', 'color' => 'blue'],
                ['slug' => 'conflict-of-interest', 'title' => 'Conflict of Interest', 'icon' => 'bi-people-fill',        'color' => 'green'],
                ['slug' => 'misuse-of-resources',  'title' => 'Misuse of Resources',  'icon' => 'bi-database-fill',      'color' => 'green'],
                ['slug' => 'abuse-of-authority',   'title' => 'Abuse of Authority',   'icon' => 'bi-person-fill',        'color' => 'blue'],
                ['slug' => 'ethical-misconduct',   'title' => 'Ethical Misconduct',   'icon' => 'bi-bank',               'color' => 'green'],
                ['slug' => 'other',                'title' => 'Other Concerns',       'icon' => 'bi-three-dots',         'color' => 'blue'],
            ] as $cat)
                <div class="col-6 col-lg-3">
                    <a href="{{ route('reports.create', ['category' => $cat['slug']]) }}"
                       class="card jiacrs-category h-100 shadow-sm">
                        <span class="jiacrs-category-icon is-{{ $cat['color'] }}"><i class="bi {{ $cat['icon'] }}"></i></span>
                        <span class="jiacrs-category-name">{{ $cat['title'] }}</span>
                        <i class="bi bi-arrow-right jiacrs-category-arrow"></i>
                    </a>
                </div>
            @endforeach
        </div>

    </div>
</section> -->