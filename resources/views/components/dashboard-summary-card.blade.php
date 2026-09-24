@props(['item'])

<article class="dashboard-summary-card">
    <span class="dashboard-summary-icon"><x-prototype-icon :name="$item['icon']" :size="24" /></span>
    <div>
        <p class="dashboard-summary-label">{{ $item['label'] }}</p>
        <p class="dashboard-summary-value">{{ $item['value'] }}</p>
        <p class="dashboard-summary-meta">{{ $item['meta'] }}</p>
    </div>
</article>
