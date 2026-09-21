<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            height: 100vh;
            min-height: 620px;
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

        .actions {
            position: absolute;
            z-index: 500;
            right: 14px;
            bottom: 94px;
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
            height: 66px;
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
            bottom: 76px;
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
            min-width: 50px;
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
            font-size: 17px;
            font-weight: 800;
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

        .notice {
            position: absolute;
            z-index: 550;
            left: 14px;
            right: 14px;
            bottom: 156px;
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
            .app {
                max-width: 1120px;
                height: min(820px, calc(100vh - 32px));
                margin: 16px auto;
                border: 1px solid var(--line);
                border-radius: 8px;
                box-shadow: var(--shadow);
            }

            .drawer {
                left: auto;
                right: 14px;
                width: 360px;
                bottom: 92px;
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
            <input id="searchInput" type="search" placeholder="Tim cua hang">
        </label>
        <button class="icon-button" type="button" aria-label="Menu">☰</button>
    </div>

    <div class="actions" aria-label="Tac vu nhanh">
        <button class="icon-button" id="locateButton" type="button" aria-label="Ve vi tri hien tai">◎</button>
        <button class="icon-button" id="addButton" type="button" aria-label="Them dia diem">＋</button>
    </div>

    <section class="drawer" id="listDrawer" aria-label="Danh sach diem ban">
        <div class="drawer-header">
            <h1 class="drawer-title">Diem ban dang co thong tin</h1>
            <span class="meta" id="resultCount"></span>
        </div>
        <div class="store-list" id="storeList"></div>
    </section>

    <div class="notice" id="notice" role="status"></div>

    <nav class="bottom-tabs" aria-label="Che do xem">
        <button class="tab active" id="mapTab" type="button">🗺 MAP</button>
        <button class="tab" id="listTab" type="button">📋 DANH SACH</button>
    </nav>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const reports = [
        {
            id: 1,
            store: 'Joshin Hirakata',
            address: 'Hirakata, Osaka',
            lat: 34.8147,
            lng: 135.6500,
            product: 'MEGAドリームex',
            quantity: 20,
            saleAt: '2026/09/21 10:00',
            updatedAgo: '5 phut truoc',
            reporter: 'User A',
            note: '1 nguoi toi da 1 BOX',
            status: 'active',
        },
        {
            id: 2,
            store: 'Yodobashi Umeda',
            address: 'Umeda, Osaka',
            lat: 34.7043,
            lng: 135.4966,
            product: 'ロケット団の栄光',
            quantity: 10,
            saleAt: '2026/09/21 15:00',
            updatedAgo: '18 phut truoc',
            reporter: 'User B',
            note: 'Xep hang truoc quay gachapon',
            status: 'scheduled',
        },
        {
            id: 3,
            store: 'Pokemon Center Osaka',
            address: 'Osaka Station City',
            lat: 34.7024,
            lng: 135.4959,
            product: 'ブラックボルト',
            quantity: 5,
            saleAt: '2026/09/21 09:30',
            updatedAgo: '2 gio truoc',
            reporter: 'User C',
            note: 'Thong tin can kiem tra lai',
            status: 'expired',
        },
    ];

    const statusLabels = {
        active: 'Dang ban',
        scheduled: 'Sap ban',
        expired: 'Het han',
    };

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

    const listDrawer = document.querySelector('#listDrawer');
    const mapTab = document.querySelector('#mapTab');
    const listTab = document.querySelector('#listTab');
    const storeList = document.querySelector('#storeList');
    const resultCount = document.querySelector('#resultCount');
    const notice = document.querySelector('#notice');
    const searchInput = document.querySelector('#searchInput');
    let userMarker = null;
    const markerById = new Map();

    function markerIcon(report) {
        return L.divIcon({
            className: '',
            html: `
                <div class="map-marker ${report.status}">
                    <div class="qty">${report.quantity}</div>
                    <div class="unit">BOX</div>
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
                <h2>${report.store}</h2>
                <dl>
                    <dt>San pham</dt><dd>${report.product}</dd>
                    <dt>So luong</dt><dd>${report.quantity} BOX</dd>
                    <dt>Thoi gian</dt><dd>${report.saleAt}</dd>
                    <dt>Nguoi bao</dt><dd>${report.reporter}</dd>
                    <dt>Cap nhat</dt><dd>${report.updatedAgo}</dd>
                    <dt>Ghi chu</dt><dd>${report.note}</dd>
                </dl>
                <a href="${directionsUrl(report)}" target="_blank" rel="noopener">Google Mapsで経路案内</a>
                <button type="button" onclick="showNotice('Chuc nang cap nhat se lam o buoc tiep theo')">Cap nhat thong tin</button>
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
        resultCount.textContent = `${items.length} diem`;
        storeList.innerHTML = items.map((report) => `
            <button class="store-card" type="button" data-id="${report.id}">
                <strong>${report.store}</strong>
                <span class="meta">${report.product} · ${report.quantity} BOX · ${report.saleAt}</span>
                <span class="badge-row">
                    <span class="badge ${report.status}">${statusLabels[report.status]}</span>
                    <span class="meta">${report.updatedAgo}</span>
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
            ].some((value) => value.toLowerCase().includes(keyword));
        });

        renderMarkers(items);
        renderList(items);
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
        listTab.classList.toggle('active', open);
        mapTab.classList.toggle('active', !open);
    }

    function showNotice(message) {
        notice.textContent = message;
        notice.classList.add('show');
        window.clearTimeout(showNotice.timer);
        showNotice.timer = window.setTimeout(() => notice.classList.remove('show'), 2600);
    }

    document.querySelector('#locateButton').addEventListener('click', () => {
        if (!navigator.geolocation) {
            showNotice('Trinh duyet khong ho tro lay vi tri hien tai');
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
                }).addTo(map).bindPopup('Ban dang o day');
            }

            map.setView(latLng, 15);
            userMarker.openPopup();
        }, () => {
            showNotice('Khong the lay vi tri. Hay cho phep location trong trinh duyet.');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
        });
    });

    document.querySelector('#addButton').addEventListener('click', () => {
        showNotice('Buoc tiep theo se them form dang thong tin ban hang');
    });

    mapTab.addEventListener('click', () => setDrawer(false));
    listTab.addEventListener('click', () => setDrawer(true));
    searchInput.addEventListener('input', filterReports);

    storeList.addEventListener('click', (event) => {
        const card = event.target.closest('.store-card');
        if (card) {
            openReport(Number(card.dataset.id));
        }
    });

    window.showNotice = showNotice;
    filterReports();
</script>
</body>
</html>
