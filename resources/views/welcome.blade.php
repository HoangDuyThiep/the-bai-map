<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>The Bai Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            color-scheme: light;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #637083;
            --line: #d9e0ea;
            --accent: #0d9488;
            --accent-strong: #0f766e;
            --warning: #f59e0b;
            --expired: #6b7280;
            --shadow: 0 12px 32px rgba(15, 23, 42, .18);
            --app-height: 100dvh;
            --bottom-safe: max(14px, env(safe-area-inset-bottom));
            --tab-height: 66px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: #eef3f8;
        }

        button,
        input {
            font: inherit;
        }

        .app {
            position: relative;
            height: var(--app-height);
            min-height: 0;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .top-bar {
            position: absolute;
            z-index: 500;
            top: 14px;
            left: 12px;
            right: 12px;
            display: grid;
            grid-template-columns: 1fr 46px;
            gap: 10px;
            pointer-events: none;
        }

        .search {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 46px;
            padding: 0 14px;
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
            pointer-events: auto;
        }

        .search input {
            width: 100%;
            border: 0;
            outline: 0;
            color: var(--text);
            background: transparent;
        }

        .search-results {
            position: absolute;
            z-index: 540;
            top: 68px;
            left: 12px;
            right: 68px;
            display: none;
            max-height: 310px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .search-results.open {
            display: block;
        }

        .place-result {
            display: grid;
            gap: 4px;
            width: 100%;
            padding: 11px 13px;
            border: 0;
            border-bottom: 1px solid var(--line);
            color: var(--text);
            background: transparent;
            text-align: left;
            cursor: pointer;
        }

        .place-result:last-child {
            border-bottom: 0;
        }

        .place-result:hover {
            background: #eef3f8;
        }

        .place-result strong {
            font-size: 14px;
        }

        .search-source {
            padding: 8px 13px;
            color: var(--muted);
            background: #f8fafc;
            font-size: 12px;
        }

        .icon-button {
            display: inline-grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border: 0;
            border-radius: 8px;
            color: var(--text);
            background: var(--panel);
            box-shadow: var(--shadow);
            cursor: pointer;
            pointer-events: auto;
        }

        .account-menu {
            position: absolute;
            z-index: 560;
            top: 68px;
            right: 12px;
            display: none;
            min-width: 210px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .account-menu.open {
            display: block;
        }

        .account-menu-header,
        .account-menu a,
        .account-menu button {
            display: block;
            width: 100%;
            padding: 11px 13px;
            border: 0;
            color: var(--text);
            background: transparent;
            text-align: left;
            text-decoration: none;
            font-size: 14px;
        }

        .account-menu-header {
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-weight: 700;
        }

        .account-menu a:hover,
        .account-menu button:hover {
            background: #eef3f8;
        }

        .actions {
            position: absolute;
            z-index: 500;
            right: 14px;
            bottom: calc(var(--tab-height) + var(--bottom-safe) + 28px);
            display: grid;
            gap: 10px;
        }

        .bottom-tabs {
            position: absolute;
            z-index: 500;
            left: 0;
            right: 0;
            bottom: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: calc(var(--tab-height) + var(--bottom-safe));
            padding-bottom: var(--bottom-safe);
            border-top: 1px solid var(--line);
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(10px);
        }

        .tab {
            border: 0;
            background: transparent;
            color: var(--muted);
            font-weight: 700;
            cursor: pointer;
        }

        .tab.active {
            color: var(--accent-strong);
        }

        .drawer {
            position: absolute;
            z-index: 520;
            left: 10px;
            right: 10px;
            bottom: calc(var(--tab-height) + var(--bottom-safe) + 10px);
            max-height: 54vh;
            overflow: auto;
            transform: translateY(calc(100% + 90px));
            transition: transform .22s ease;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .drawer.open {
            transform: translateY(0);
        }

        .drawer-header {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 14px 10px;
            border-bottom: 1px solid var(--line);
            background: var(--panel);
        }

        .drawer-title {
            margin: 0;
            font-size: 15px;
        }

        .store-list {
            display: grid;
            gap: 10px;
            padding: 12px;
        }

        .store-card {
            display: grid;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfcfe;
            cursor: pointer;
            text-align: left;
        }

        .store-card strong {
            font-size: 15px;
        }

        .meta {
            color: var(--muted);
            font-size: 13px;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 999px;
            color: #ffffff;
            background: var(--accent);
            font-size: 12px;
            font-weight: 700;
        }

        .badge.scheduled {
            background: var(--warning);
        }

        .badge.expired {
            background: var(--expired);
        }

        .map-marker {
            display: grid;
            place-items: center;
            min-width: 58px;
            min-height: 50px;
            padding: 6px 8px;
            border: 2px solid #ffffff;
            border-radius: 8px;
            color: #ffffff;
            background: var(--accent);
            box-shadow: 0 8px 20px rgba(15, 23, 42, .24);
            line-height: 1;
            text-align: center;
        }

        .map-marker.scheduled {
            background: var(--warning);
        }

        .map-marker.expired {
            background: var(--expired);
        }

        .map-marker .qty {
            font-size: 14px;
            font-weight: 800;
            max-width: 74px;
            overflow-wrap: anywhere;
        }

        .map-marker .unit {
            margin-top: 3px;
            font-size: 10px;
            font-weight: 800;
        }

        .popup {
            min-width: 220px;
        }

        .popup h2 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .popup dl {
            display: grid;
            grid-template-columns: 88px 1fr;
            gap: 6px 8px;
            margin: 0 0 12px;
            font-size: 13px;
        }

        .popup dt {
            color: var(--muted);
        }

        .popup dd {
            margin: 0;
            font-weight: 650;
        }

        .popup a,
        .popup button {
            display: block;
            width: 100%;
            margin-top: 8px;
            padding: 9px 10px;
            border: 0;
            border-radius: 8px;
            color: #ffffff;
            background: var(--accent-strong);
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .popup button {
            color: var(--text);
            background: #e6edf5;
        }

        .report-form {
            display: grid;
            gap: 12px;
            padding: 12px;
        }

        .report-form label {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .report-form input,
        .report-form select,
        .report-form textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 11px;
            color: var(--text);
            background: #ffffff;
            outline: 0;
        }

        .report-form input:focus,
        .report-form select:focus,
        .report-form textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, .16);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .primary-button,
        .secondary-button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 11px 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .primary-button {
            color: #ffffff;
            background: var(--accent-strong);
        }

        .primary-button:disabled {
            opacity: .68;
            cursor: wait;
        }

        .secondary-button {
            color: var(--text);
            background: #e6edf5;
        }

        .form-errors {
            display: grid;
            gap: 6px;
            padding: 10px 12px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            background: #fef2f2;
            font-size: 13px;
        }

        .field-error {
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
        }

        .is-hidden {
            display: none;
        }

        .notice {
            position: absolute;
            z-index: 550;
            left: 14px;
            right: 14px;
            bottom: calc(var(--tab-height) + var(--bottom-safe) + 90px);
            display: none;
            padding: 10px 12px;
            border-radius: 8px;
            color: #ffffff;
            background: rgba(15, 23, 42, .9);
            font-size: 13px;
        }

        .notice.show {
            display: block;
        }

        @media (min-width: 760px) {
            :root {
                --app-height: min(820px, calc(100vh - 32px));
                --bottom-safe: 0px;
            }

            .app {
                max-width: 1120px;
                height: var(--app-height);
                min-height: 620px;
                margin: 16px auto;
                border: 1px solid var(--line);
                border-radius: 8px;
                box-shadow: var(--shadow);
            }

            .drawer {
                left: auto;
                right: 14px;
                width: 360px;
                bottom: calc(var(--tab-height) + 26px);
            }
        }
    </style>
</head>
<body>
<main class="app">
    <div id="map" aria-label="Ban do diem ban"></div>

    <div class="top-bar">
        <label class="search" aria-label="Tim cua hang">
            <span aria-hidden="true">🔍</span>
            <input id="searchInput" type="search" placeholder="Tìm cửa hàng">
        </label>
        <button class="icon-button" id="accountMenuButton" type="button" aria-label="Menu">☰</button>
    </div>

    <section class="search-results" id="searchResults" aria-label="Kết quả tìm địa điểm"></section>

    <div class="account-menu" id="accountMenu">
        <div class="account-menu-header">{{ Auth::user()->name }}</div>
        @if (Auth::user()->isAdmin())
            <a href="{{ route('admin.users') }}">Duyệt thành viên</a>
        @endif
        <a href="{{ route('rank') }}">Bảng xếp hạng</a>
        <a href="{{ route('profile.edit') }}">Hồ sơ</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Đăng xuất</button>
        </form>
    </div>

    <div class="actions" aria-label="Tác vụ nhanh">
        <button class="icon-button" id="locateButton" type="button" aria-label="Về vị trí hiện tại">◎</button>
        <button class="icon-button" id="addButton" type="button" aria-label="Thêm địa điểm">＋</button>
    </div>

    <section class="drawer" id="listDrawer" aria-label="Danh sách điểm bán">
        <div class="drawer-header">
            <h1 class="drawer-title">Điểm bán đang có thông tin</h1>
            <span class="meta" id="resultCount"></span>
        </div>
        <div class="store-list" id="storeList"></div>
    </section>

    <section class="drawer {{ $errors->any() ? 'open' : '' }}" id="reportFormDrawer" aria-label="Thêm thông tin bán hàng">
        <div class="drawer-header">
            <h1 class="drawer-title">Thêm thông tin</h1>
            <button class="tab" id="closeFormButton" type="button">Đóng</button>
        </div>

        <form class="report-form" id="reportForm" method="POST" action="{{ route('reports.store') }}" novalidate>
            @csrf
            <input id="methodInput" type="hidden" name="_method" value="">
            <input id="clientTokenInput" type="hidden" name="client_token" value="">

            @if ($errors->any())
                <div class="form-errors" role="alert">
                    <strong>Vui lòng kiểm tra lại thông tin.</strong>
                    @foreach ($errors->all() as $error)
                        <span>{{ $error }}</span>
                    @endforeach
                </div>
            @endif

            <label>
                Tên cửa hàng
                <input name="store_name" id="storeNameInput" type="text" required placeholder="Joshin Hirakata" value="{{ old('store_name') }}">
                @error('store_name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Địa chỉ
                <input name="address" id="addressInput" type="text" placeholder="Hirakata, Osaka" value="{{ old('address') }}">
                @error('address')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <div class="form-grid">
                <label>
                    Latitude
                    <input name="latitude" id="latitudeInput" type="number" step="0.0000001" required value="{{ old('latitude') }}">
                    @error('latitude')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Longitude
                    <input name="longitude" id="longitudeInput" type="number" step="0.0000001" required value="{{ old('longitude') }}">
                    @error('longitude')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>
            </div>

            <button class="secondary-button" id="useCurrentLocationButton" type="button">
                Lấy vị trí hiện tại
            </button>

            <label>
                Sản phẩm
                <input name="product_name" id="productNameInput" type="text" required placeholder="MEGAドリームex" value="{{ old('product_name') }}">
                @error('product_name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Số lượng
                <input name="quantity_text" id="quantityTextInput" type="text" required placeholder="1 box hoặc 5 pack" value="{{ old('quantity_text') }}">
                @error('quantity_text')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Trạng thái
                <select name="status" id="statusInput" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Đang bán</option>
                    <option value="scheduled" @selected(old('status') === 'scheduled')>Sắp bán</option>
                    <option value="sold_out" @selected(old('status') === 'sold_out')>Hết bán</option>
                </select>
                @error('status')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <label id="saleAtField">
                Giờ bán
                <input name="sale_at" id="saleAtInput" type="datetime-local" value="{{ old('sale_at') }}">
                @error('sale_at')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Ghi chú
                <textarea name="note" id="noteInput" rows="3" placeholder="Mỗi người tối đa 5 pack">{{ old('note') }}</textarea>
                @error('note')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>

            <button class="primary-button" id="submitReportButton" type="submit">Đăng thông tin</button>
        </form>
    </section>

    <div class="notice" id="notice" role="status"></div>

    <nav class="bottom-tabs" aria-label="Chế độ xem">
        <button class="tab active" id="mapTab" type="button">🗺 MAP</button>
        <button class="tab" id="listTab" type="button">📋 DANH SÁCH</button>
    </nav>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const reports = @json($reports);
    const csrfToken = @json(csrf_token());
    const flashStatus = @json(session('status'));

    const statusLabels = {
        active: 'Đang bán',
        scheduled: 'Sắp bán',
        expired: 'Hết hạn',
    };

    function updateAppHeight() {
        const height = window.visualViewport?.height ?? window.innerHeight;
        document.documentElement.style.setProperty('--app-height', `${height}px`);
    }

    updateAppHeight();

    const map = L.map('map', {
        zoomControl: false,
    }).setView([34.7043, 135.5050], 11);

    L.control.zoom({
        position: 'bottomleft',
    }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    function refreshViewportLayout() {
        updateAppHeight();
        window.setTimeout(() => map.invalidateSize(), 80);
    }

    window.addEventListener('resize', refreshViewportLayout);
    window.visualViewport?.addEventListener('resize', refreshViewportLayout);
    window.visualViewport?.addEventListener('scroll', refreshViewportLayout);

    const listDrawer = document.querySelector('#listDrawer');
    const reportFormDrawer = document.querySelector('#reportFormDrawer');
    const closeFormButton = document.querySelector('#closeFormButton');
    const mapTab = document.querySelector('#mapTab');
    const listTab = document.querySelector('#listTab');
    const storeList = document.querySelector('#storeList');
    const resultCount = document.querySelector('#resultCount');
    const notice = document.querySelector('#notice');
    const searchInput = document.querySelector('#searchInput');
    const searchResults = document.querySelector('#searchResults');
    const accountMenu = document.querySelector('#accountMenu');
    const accountMenuButton = document.querySelector('#accountMenuButton');
    const latitudeInput = document.querySelector('#latitudeInput');
    const longitudeInput = document.querySelector('#longitudeInput');
    const reportForm = document.querySelector('#reportForm');
    const methodInput = document.querySelector('#methodInput');
    const clientTokenInput = document.querySelector('#clientTokenInput');
    const storeNameInput = document.querySelector('#storeNameInput');
    const addressInput = document.querySelector('#addressInput');
    const productNameInput = document.querySelector('#productNameInput');
    const quantityTextInput = document.querySelector('#quantityTextInput');
    const useCurrentLocationButton = document.querySelector('#useCurrentLocationButton');
    const statusInput = document.querySelector('#statusInput');
    const saleAtField = document.querySelector('#saleAtField');
    const saleAtInput = document.querySelector('#saleAtInput');
    const noteInput = document.querySelector('#noteInput');
    const submitReportButton = document.querySelector('#submitReportButton');
    let userMarker = null;
    let draftStoreMarker = null;
    let selectedPlace = null;
    let selectedPlaceMarker = null;
    const markerById = new Map();

    function makeClientToken() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character]));
    }

    function markerIcon(report) {
        return L.divIcon({
            className: '',
            html: `
                <div class="map-marker ${report.status}">
                    <div class="qty">${escapeHtml(report.quantityText)}</div>
                </div>
            `,
            iconSize: [56, 56],
            iconAnchor: [28, 28],
            popupAnchor: [0, -28],
        });
    }

    function directionsUrl(report) {
        return `https://www.google.com/maps/dir/?api=1&destination=${report.lat},${report.lng}`;
    }

    function popupHtml(report) {
        return `
            <div class="popup">
                <h2>${escapeHtml(report.store)}</h2>
                <dl>
                    <dt>Sản phẩm</dt><dd>${escapeHtml(report.product)}</dd>
                    <dt>Số lượng</dt><dd>${escapeHtml(report.quantityText)}</dd>
                    <dt>Thời gian</dt><dd>${escapeHtml(report.saleAt)}</dd>
                    <dt>Người báo</dt><dd>${escapeHtml(report.reporter)}</dd>
                    <dt>Cập nhật</dt><dd>${escapeHtml(report.updatedAt)}</dd>
                    <dt>Hữu ích</dt><dd>${report.helpfulCount}</dd>
                    <dt>Ghi chú</dt><dd>${escapeHtml(report.note)}</dd>
                </dl>
                <a href="${directionsUrl(report)}" target="_blank" rel="noopener">Google Mapsで経路案内</a>
                <form method="POST" action="/reports/${report.id}/helpful">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <button type="submit">Hữu ích</button>
                </form>
                <button type="button" onclick="openReportEditor(${report.id})">Cập nhật thông tin</button>
            </div>
        `;
    }

    function renderMarkers(items) {
        markerById.forEach((marker) => map.removeLayer(marker));
        markerById.clear();

        items.forEach((report) => {
            const marker = L.marker([report.lat, report.lng], {
                icon: markerIcon(report),
            }).addTo(map);

            marker.bindPopup(popupHtml(report));
            markerById.set(report.id, marker);
        });
    }

    function renderList(items) {
        resultCount.textContent = `${items.length} bài`;
        storeList.innerHTML = items.map((report) => `
            <button class="store-card" type="button" data-id="${report.id}">
                <strong>${escapeHtml(report.store)}</strong>
                <span class="meta">${escapeHtml(report.product)} · ${escapeHtml(report.quantityText)} · ${escapeHtml(report.saleAt)}</span>
                <span class="badge-row">
                    <span class="badge ${report.status}">${statusLabels[report.status]}</span>
                    <span class="meta">Cập nhật ${escapeHtml(report.updatedAt)}</span>
                    <span class="meta">Hữu ích ${report.helpfulCount}</span>
                </span>
            </button>
        `).join('');
    }

    function filterReports() {
        const keyword = searchInput.value.trim().toLowerCase();
        const items = reports.filter((report) => {
            return [
                report.store,
                report.address,
                report.product,
            ].some((value) => String(value ?? '').toLowerCase().includes(keyword));
        });

        renderMarkers(items);
        renderList(items);
    }

    function renderSearchResults(places) {
        if (!places.length) {
            searchResults.innerHTML = `
                <div class="search-source">Không tìm thấy địa điểm phù hợp.</div>
            `;
            searchResults.classList.add('open');
            return;
        }

        searchResults.innerHTML = `
            ${places.map((place, index) => `
                <button class="place-result" type="button" data-index="${index}">
                    <strong>${escapeHtml(place.name)}</strong>
                    <span class="meta">${escapeHtml(place.address)}</span>
                </button>
            `).join('')}
            <div class="search-source">Kết quả từ OpenStreetMap</div>
        `;
        searchResults.classList.add('open');
    }

    async function searchPlaces() {
        const keyword = searchInput.value.trim();

        if (keyword.length < 2) {
            showNotice('Nhập ít nhất 2 ký tự để tìm địa điểm');
            return;
        }

        showNotice('Đang tìm địa điểm...');

        try {
            const response = await fetch(`/places/search?q=${encodeURIComponent(keyword)}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Search failed');
            }

            const data = await response.json();
            searchResults.places = data.places ?? [];
            renderSearchResults(searchResults.places);
        } catch (error) {
            searchResults.classList.remove('open');
            showNotice('Không thể tìm địa điểm lúc này');
        }
    }

    function fillPlaceToForm(place) {
        storeNameInput.value = place.name ?? '';
        addressInput.value = place.address ?? '';
        setDraftStoreLocation({
            lat: place.lat,
            lng: place.lng,
        });
    }

    function selectPlace(place) {
        selectedPlace = place;
        const latLng = [place.lat, place.lng];

        if (selectedPlaceMarker) {
            selectedPlaceMarker.setLatLng(latLng);
        } else {
            selectedPlaceMarker = L.marker(latLng).addTo(map);
        }

        selectedPlaceMarker
            .bindPopup(`<strong>${escapeHtml(place.name)}</strong><br>${escapeHtml(place.address)}`)
            .openPopup();

        map.setView(latLng, 16);
        setDrawer(false);
        searchResults.classList.remove('open');
        showNotice('Đã chọn địa điểm. Bấm + để đăng thông tin tại đây.');
    }

    function openReport(id) {
        const report = reports.find((item) => item.id === id);
        const marker = markerById.get(id);
        if (!report || !marker) {
            return;
        }

        map.setView([report.lat, report.lng], 15);
        marker.openPopup();
        setDrawer(false);
    }

    function setDrawer(open) {
        listDrawer.classList.toggle('open', open);
        if (open) {
            reportFormDrawer.classList.remove('open');
        }
        listTab.classList.toggle('active', open);
        mapTab.classList.toggle('active', !open);
    }

    function setReportForm(open) {
        reportFormDrawer.classList.toggle('open', open);
        if (open) {
            listDrawer.classList.remove('open');
            showNotice('Bấm vào bản đồ để chọn vị trí cửa hàng');
        }
    }

    function resetReportForm() {
        reportForm.action = @json(route('reports.store'));
        methodInput.value = '';
        clientTokenInput.value = makeClientToken();
        storeNameInput.value = '';
        addressInput.value = '';
        latitudeInput.value = '';
        longitudeInput.value = '';
        productNameInput.value = '';
        quantityTextInput.value = '';
        statusInput.value = 'active';
        saleAtInput.value = '';
        noteInput.value = '';
        submitReportButton.disabled = false;
        submitReportButton.textContent = 'Đăng thông tin';
        toggleSaleAtField();
    }

    function openReportEditor(id) {
        const report = reports.find((item) => item.id === id);
        if (!report) {
            return;
        }

        reportForm.action = `/reports/${report.id}`;
        methodInput.value = 'PATCH';
        clientTokenInput.value = '';
        storeNameInput.value = report.store ?? '';
        addressInput.value = report.address ?? '';
        latitudeInput.value = report.lat;
        longitudeInput.value = report.lng;
        productNameInput.value = report.product ?? '';
        quantityTextInput.value = report.quantityText ?? '';
        statusInput.value = report.status ?? 'active';
        saleAtInput.value = report.saleAtInput ?? '';
        noteInput.value = report.note ?? '';
        submitReportButton.disabled = false;
        submitReportButton.textContent = 'Cập nhật thông tin';
        toggleSaleAtField();
        setDraftStoreLocation({
            lat: report.lat,
            lng: report.lng,
        });
        setReportForm(true);
    }

    function setDraftStoreLocation(latLng) {
        latitudeInput.value = latLng.lat.toFixed(7);
        longitudeInput.value = latLng.lng.toFixed(7);

        if (draftStoreMarker) {
            draftStoreMarker.setLatLng(latLng);
        } else {
            draftStoreMarker = L.marker(latLng, {
                draggable: true,
            }).addTo(map).bindPopup('Vị trí cửa hàng mới');

            draftStoreMarker.on('dragend', () => {
                const markerLatLng = draftStoreMarker.getLatLng();
                latitudeInput.value = markerLatLng.lat.toFixed(7);
                longitudeInput.value = markerLatLng.lng.toFixed(7);
            });
        }

        draftStoreMarker.openPopup();
    }

    function showNotice(message) {
        notice.textContent = message;
        notice.classList.add('show');
        window.clearTimeout(showNotice.timer);
        showNotice.timer = window.setTimeout(() => notice.classList.remove('show'), 2600);
    }

    function toggleSaleAtField() {
        const isScheduled = statusInput.value === 'scheduled';
        saleAtField.classList.toggle('is-hidden', !isScheduled);
        saleAtInput.required = isScheduled;
    }

    function locateUser({ setView = true, openPopup = true } = {}) {
        if (!navigator.geolocation) {
            showNotice('Trình duyệt không hỗ trợ lấy vị trí hiện tại');
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            const latLng = [position.coords.latitude, position.coords.longitude];
            if (userMarker) {
                userMarker.setLatLng(latLng);
            } else {
                userMarker = L.circleMarker(latLng, {
                    radius: 9,
                    color: '#ffffff',
                    weight: 3,
                    fillColor: '#2563eb',
                    fillOpacity: 1,
                }).addTo(map).bindPopup('Bạn đang ở đây');
            }

            if (setView) {
                map.setView(latLng, 15);
            }

            if (openPopup) {
                userMarker.openPopup();
            }
        }, () => {
            showNotice('Không thể lấy vị trí. Hãy cho phép location trong trình duyệt.');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
        });
    }

    document.querySelector('#locateButton').addEventListener('click', () => {
        locateUser();
    });

    document.querySelector('#addButton').addEventListener('click', () => {
        resetReportForm();
        if (selectedPlace) {
            fillPlaceToForm(selectedPlace);
        }
        setReportForm(true);
    });

    accountMenuButton.addEventListener('click', () => {
        accountMenu.classList.toggle('open');
    });

    useCurrentLocationButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showNotice('Trình duyệt không hỗ trợ lấy vị trí hiện tại');
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            latitudeInput.value = position.coords.latitude.toFixed(7);
            longitudeInput.value = position.coords.longitude.toFixed(7);
            setDraftStoreLocation({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            });
            showNotice('Đã điền vị trí hiện tại vào form');
        }, () => {
            showNotice('Không thể lấy vị trí. Hãy cho phép location trong trình duyệt.');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
        });
    });

    closeFormButton.addEventListener('click', () => {
        setReportForm(false);
    });

    reportForm.addEventListener('submit', () => {
        if (!methodInput.value && !clientTokenInput.value) {
            clientTokenInput.value = makeClientToken();
        }

        submitReportButton.disabled = true;
        submitReportButton.textContent = methodInput.value === 'PATCH'
            ? 'Đang cập nhật...'
            : 'Đang đăng...';
    });

    statusInput.addEventListener('change', toggleSaleAtField);

    map.on('click', (event) => {
        if (!reportFormDrawer.classList.contains('open')) {
            return;
        }

        setDraftStoreLocation(event.latlng);
        showNotice('Đã chọn vị trí cửa hàng trên bản đồ');
    });

    mapTab.addEventListener('click', () => setDrawer(false));
    listTab.addEventListener('click', () => setDrawer(true));
    searchInput.addEventListener('input', () => {
        searchResults.classList.remove('open');
        filterReports();
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        searchPlaces();
    });

    searchResults.addEventListener('click', (event) => {
        const button = event.target.closest('.place-result');
        if (!button) {
            return;
        }

        const place = searchResults.places?.[Number(button.dataset.index)];
        if (place) {
            selectPlace(place);
        }
    });

    storeList.addEventListener('click', (event) => {
        const card = event.target.closest('.store-card');
        if (card) {
            openReport(Number(card.dataset.id));
        }
    });

    window.openReportEditor = openReportEditor;
    window.showNotice = showNotice;
    toggleSaleAtField();
    filterReports();
    locateUser({ setView: true, openPopup: false });

    if (flashStatus) {
        showNotice(flashStatus);
    }
</script>
</body>
</html>
