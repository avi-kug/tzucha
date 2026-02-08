// General JS
console.log('Tzucha system loaded');

// Expenses tabs persistence (per-page guard)
document.addEventListener('DOMContentLoaded', function () {
	const tabsContainer = document.getElementById('expensesTabs');
	if (!tabsContainer) return;

	const STORAGE_KEY = 'expenses_active_tab';
	const filterTabInput = document.getElementById('filter_tab');
	const clearFiltersLink = document.getElementById('clearFiltersLink');

	function setUrlTab(tab) {
		try {
			const url = new URL(window.location.href);
			url.searchParams.set('tab', tab);
			window.history.replaceState({}, '', url);
		} catch (e) {
			// ignore URL errors
		}
	}

	function setFilterTargets(tab) {
		if (filterTabInput) filterTabInput.value = tab;
		if (clearFiltersLink) clearFiltersLink.href = 'expenses.php?tab=' + encodeURIComponent(tab);
	}
	function setTabCookie(tab) {
		try {
			document.cookie = 'expenses_tab=' + encodeURIComponent(tab) + '; path=/; max-age=31536000';
		} catch (e) {}
	}

	// On tab shown: persist, update URL and filter targets
	tabsContainer.querySelectorAll('a[data-bs-toggle="tab"], button[data-bs-toggle="tab"]').forEach(function (btn) {
		btn.addEventListener('shown.bs.tab', function (e) {
			const targetSelector = e.target.getAttribute('data-bs-target');
			if (!targetSelector) return;
			const tab = targetSelector.replace('#', '');
			localStorage.setItem(STORAGE_KEY, tab);
			setTabCookie(tab);
			// use pushState so the param shows immediately while staying on page
			try {
				const url = new URL(window.location.href);
				url.searchParams.set('tab', tab);
				window.history.pushState({}, '', url);
			} catch (e2) {
				setUrlTab(tab);
			}
			setFilterTargets(tab);
		});
	});

	// On first load: if no URL tab, use saved; activate and sync
	(function initFromState() {
		const url = new URL(window.location.href);
		let tab = url.searchParams.get('tab');
		const validTabs = ['fixed', 'regular', 'combined', 'dashboard', 'data'];
		if (!tab) {
			const saved = localStorage.getItem(STORAGE_KEY);
			if (saved && validTabs.includes(saved)) {
				tab = saved;
				// Activate the tab visually
				const trigger = document.getElementById(saved + '-tab');
				if (trigger && window.bootstrap && bootstrap.Tab) {
					new bootstrap.Tab(trigger).show();
				}
				setTabCookie(saved);
				setUrlTab(saved);
			}
		}
		// Ensure filter targets align with whatever is active now
		// Determine active by checking button with .active or fallback to tab var
		let activeTabBtn = tabsContainer.querySelector('button.nav-link.active');
		let active = activeTabBtn ? (activeTabBtn.getAttribute('data-bs-target') || '').replace('#', '') : (tab || 'fixed');
		if (!validTabs.includes(active)) active = 'fixed';
		setFilterTargets(active);
	})();
});

document.addEventListener('DOMContentLoaded', function () {
	var meta = document.querySelector('meta[name="csrf-token"]');
	if (!meta) return;
	var token = meta.getAttribute('content') || '';
	if (!token) return;
	document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (form) {
		if (form.querySelector('input[name="csrf_token"]')) return;
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'csrf_token';
		input.value = token;
		form.appendChild(input);
	});
});

document.addEventListener('click', function (event) {
	var editBtn = event.target.closest('[data-edit-member-id]');
	if (!editBtn) return;
	var id = Number(editBtn.getAttribute('data-edit-member-id'));
	if (!Number.isFinite(id) || id <= 0) return;
	if (typeof window.editMember === 'function') {
		window.editMember(id);
	}
});