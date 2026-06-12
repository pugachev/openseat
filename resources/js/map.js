import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

// Viteバンドル済みのLをグローバルに公開（bladeのinitMapInlineがCDN二重ロードしないようにする）
window.L = L;

// Leafletのデフォルトアイコン設定（Webpack/Vite環境での問題を回避）
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

let map = null;
let markers = [];
let userMarker = null;
let currentLocation = null;

// カスタムアイコンの作成
function createCustomIcon(status) {
    const colors = {
        0: '#22c55e', // 緑（空き）
        1: '#eab308', // 黄（待ち）
        2: '#ef4444', // 赤（満席）
    };

    const color = colors[status] || '#6b7280';

    return L.divIcon({
        className: 'custom-marker',
        html: `<div style="
            background-color: ${color};
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
    });
}

// 地図の初期化
function initMap(lat = 35.6812, lng = 139.7671) {
    if (map) {
        map.remove();
    }

    map = L.map('map', {
        dragging: true,
        touchZoom: true,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true,
        tap: true,
    }).setView([lat, lng], 13);

    // 他スクリプトと競合しても操作不能にならないよう、操作系を明示的に有効化
    map.dragging.enable();
    map.touchZoom.enable();
    map.scrollWheelZoom.enable();
    map.doubleClickZoom.enable();
    map.boxZoom.enable();
    map.keyboard.enable();

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
}

// 位置情報の取得（グローバル関数として公開）
window.getCurrentLocation = async function getCurrentLocation() {
    // console.log('Map.js: getCurrentLocation 開始');
    const statusEl = document.getElementById('locationStatus');
    const updateBtn = document.getElementById('updateLocationBtn');

    if (!statusEl) {
        console.error('Map.js: locationStatus要素が見つかりません');
        alert('エラー: 位置情報ステータス要素が見つかりません');
        return null;
    }

    if (!navigator.geolocation) {
        statusEl.textContent = 'このブラウザは位置情報をサポートしていません';
        statusEl.className = 'mt-2 text-sm text-red-600';
        console.error('Map.js: Geolocation APIがサポートされていません');
        return null;
    }

    // ボタンを無効化
    if (updateBtn) {
        updateBtn.disabled = true;
        updateBtn.textContent = '位置情報取得中...';
    }

    return new Promise((resolve, reject) => {
        statusEl.textContent = '位置情報を取得中...';
        statusEl.className = 'mt-2 text-sm text-blue-600';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                currentLocation = { lat, lng };

                statusEl.textContent = `位置情報を取得しました（緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}）`;
                statusEl.className = 'mt-2 text-sm text-green-600';

                // 地図タブは既にアクティブの可能性があるが、念のため確認
                const mapTab = document.getElementById('mapTab');
                const listTab = document.getElementById('listTab');
                const listView = document.getElementById('listView');
                const mapView = document.getElementById('mapView');

                if (mapTab && mapView) {
                    // 地図タブをアクティブにする（既にアクティブの場合もある）
                    mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
                    mapTab.classList.remove('border-transparent', 'text-gray-500');
                    if (listTab) {
                        listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                        listTab.classList.add('border-transparent', 'text-gray-500');
                    }
                    if (listView) {
                        listView.classList.add('hidden');
                    }
                    mapView.classList.remove('hidden');
                }

                // 地図が初期化されていない場合は初期化
                if (!map) {
                    initMap(lat, lng);
                }

                // 地図のサイズを再計算してからズーム（少し遅延させる）
                setTimeout(() => {
                    if (map) {
                        map.invalidateSize();

                        // ユーザーの位置をマーカーで表示
                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }
                        userMarker = L.marker([lat, lng], {
                            icon: L.icon({
                                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                            }),
                        }).addTo(map);
                        userMarker.bindPopup('現在地').openPopup();

                        // 地図を現在地にズーム（ズームレベル16でより近くに）
                        map.setView([lat, lng], 16);
                    }
                }, 100);

                // 近くの店舗を取得
                fetchNearbyShops(lat, lng);

                // ボタンを再有効化
                if (updateBtn) {
                    updateBtn.disabled = false;
                    updateBtn.textContent = '📍 位置情報を更新';
                }

                resolve({ lat, lng });
            },
            (error) => {
                let message = '位置情報の取得に失敗しました';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = '位置情報の使用が拒否されました';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '位置情報が利用できません';
                        break;
                    case error.TIMEOUT:
                        message = '位置情報の取得がタイムアウトしました';
                        break;
                }
                statusEl.textContent = message;
                statusEl.className = 'mt-2 text-sm text-red-600';
                // ボタンを再有効化
                if (updateBtn) {
                    updateBtn.disabled = false;
                    updateBtn.textContent = '📍 位置情報を更新';
                }
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    });
}

// リスト表示を更新する関数（グローバル関数として公開）
window.updateShopList = function updateShopList(shops) {
    const container = document.getElementById('shopListContainer');
    if (!container) {
        console.error('リストコンテナが見つかりません');
        return;
    }

    // コンテナをクリア
    container.innerHTML = '';

    if (shops.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 col-span-full">
                <p class="text-gray-500">近くに店舗が見つかりませんでした</p>
            </div>
        `;
        return;
    }

    // 店舗カードを生成
    shops.forEach(shop => {
        const statusLabel = shop.status_label || (shop.status === 0 ? '空き' : shop.status === 1 ? '待ち' : '満席');
        const statusClass = shop.status === 0
            ? 'bg-green-100 text-green-800'
            : shop.status === 1
            ? 'bg-yellow-100 text-yellow-800'
            : 'bg-red-100 text-red-800';

        const shopCard = document.createElement('div');
        shopCard.className = 'bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow';
        shopCard.innerHTML = `
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">${shop.name}</h3>
                    <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                        ${statusLabel}
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <p class="text-gray-600 text-sm">
                        <span class="font-medium">住所:</span> ${shop.address || ''}
                    </p>
                    <p class="text-gray-600 text-sm">
                        <span class="font-medium">電話:</span> ${shop.phone || ''}
                    </p>
                    ${shop.distance ? `<p class="text-gray-600 text-sm"><span class="font-medium">距離:</span> ${shop.distance}km</p>` : ''}
                </div>

                <a href="tel:${shop.phone || ''}"
                   class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <span class="mr-2">📞</span>
                    電話する
                </a>
            </div>
        `;
        container.appendChild(shopCard);
    });
};

// 近くの店舗を取得して地図に表示（グローバル関数として公開）
// radiusを省略可能な引数にし、未指定時はAPI側のデフォルト値を使う
window.fetchNearbyShops = async function fetchNearbyShops(lat, lng, radius = null) {
    try {
        // ベースURLを考慮したAPIエンドポイント
        const baseUrl = window.APP_BASE_URL || '';
        const apiUrl = `${baseUrl}/api/shops/nearby`;

        // パラメータを組み立て
        const params = {
            latitude: lat,
            longitude: lng
        };
        if (radius !== null && radius !== undefined) {
            params.radius = radius;
        }

        const response = await axios.get(apiUrl, { params });
        const shops = response.data;

        // 既存のマーカーを削除
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];

        if (shops.length === 0) {
            const statusEl = document.getElementById('locationStatus');
            if (statusEl) {
                statusEl.textContent = '近くに店舗が見つかりませんでした';
                statusEl.className = 'mt-2 text-sm text-gray-600';
            }
            // リスト表示も更新
            if (typeof window.updateShopList === 'function') {
                window.updateShopList([]);
            }
            return;
        }

        // 店舗をマーカーで表示
        shops.forEach(shop => {
            const marker = L.marker([shop.latitude, shop.longitude], {
                icon: createCustomIcon(shop.status),
            }).addTo(map);

            marker.bindPopup(`
                <div class="text-center">
                    <strong>${shop.name}</strong><br>
                    <span class="text-sm">${shop.status_label}</span>
                </div>
            `);

            // クリックイベント
            marker.on('click', () => {
                showShopDialog(shop);
            });

            markers.push(marker);
        });

        // 現在地を中心に、すべてのマーカーが表示されるように地図を調整
        if (markers.length > 0 && userMarker) {
            const group = new L.featureGroup([...markers, userMarker]);
            const bounds = group.getBounds();
            // 現在地を中心に保ちつつ、すべてのマーカーが表示されるように調整
            map.fitBounds(bounds.pad(0.2), {
                maxZoom: 16 // 最大ズームレベルを制限して、現在地が中心に近い状態を保つ
            });
        } else if (userMarker) {
            // 店舗がない場合は現在地にズーム
            const userLat = userMarker.getLatLng().lat;
            const userLng = userMarker.getLatLng().lng;
            map.setView([userLat, userLng], 16);
        } else if (markers.length > 0) {
            // 現在地マーカーがない場合は店舗を中心に
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }

        // リスト表示も更新
        if (typeof window.updateShopList === 'function') {
            window.updateShopList(shops);
        }

    } catch (error) {
        console.error('店舗情報の取得に失敗しました:', error);
        alert('店舗情報の取得に失敗しました');
        // エラー時もリストを空にする
        if (typeof window.updateShopList === 'function') {
            window.updateShopList([]);
        }
    }
}

// 店舗情報ダイアログを表示
function showShopDialog(shop) {
    const dialog = document.getElementById('shopDialog');
    const nameEl = document.getElementById('dialogShopName');
    const contentEl = document.getElementById('dialogShopContent');
    const phoneEl = document.getElementById('dialogShopPhone');

    nameEl.textContent = shop.name;

    // ステータスバッジ
    const statusColors = {
        0: 'bg-green-100 text-green-800',
        1: 'bg-yellow-100 text-yellow-800',
        2: 'bg-red-100 text-red-800',
    };

    contentEl.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="px-3 py-1 rounded-full text-sm font-medium ${statusColors[shop.status] || 'bg-gray-100 text-gray-800'}">
                ${shop.status_label}
            </span>
        </div>
        <p class="text-gray-600">
            <span class="font-medium">住所:</span> ${shop.address}
        </p>
        <p class="text-gray-600">
            <span class="font-medium">電話:</span> ${shop.phone}
        </p>
        ${shop.distance ? `<p class="text-gray-600"><span class="font-medium">距離:</span> ${shop.distance}km</p>` : ''}
    `;

    phoneEl.href = `tel:${shop.phone}`;

    dialog.classList.remove('hidden');
}

// タブ切り替え
function setupTabs() {
    const listTab = document.getElementById('listTab');
    const mapTab = document.getElementById('mapTab');
    const listView = document.getElementById('listView');
    const mapView = document.getElementById('mapView');

    if (!listTab || !mapTab || !listView || !mapView) {
        console.error('タブ要素が見つかりません');
        return;
    }

    // 初期状態を設定（地図タブがアクティブ）
    mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    mapTab.classList.remove('border-transparent', 'text-gray-500');
    listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
    listTab.classList.add('border-transparent', 'text-gray-500');
    listView.classList.add('hidden');
    mapView.classList.remove('hidden');

    listTab.addEventListener('click', () => {
        listTab.classList.add('active', 'border-blue-500', 'text-blue-600');
        listTab.classList.remove('border-transparent', 'text-gray-500');
        mapTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
        mapTab.classList.add('border-transparent', 'text-gray-500');
        listView.classList.remove('hidden');
        mapView.classList.add('hidden');
    });

    mapTab.addEventListener('click', () => {
        mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
        mapTab.classList.remove('border-transparent', 'text-gray-500');
        listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
        listTab.classList.add('border-transparent', 'text-gray-500');
        listView.classList.add('hidden');
        mapView.classList.remove('hidden');

        // 地図がまだ初期化されていない場合は初期化
        if (!map) {
            initMap();
        } else {
            // 地図のサイズを再計算
            setTimeout(() => {
                map.invalidateSize();
            }, 100);
        }
    });
}

// ダイアログを閉じる
function setupDialog() {
    const dialog = document.getElementById('shopDialog');
    const closeBtn = document.getElementById('closeDialogBtn');

    closeBtn.addEventListener('click', () => {
        dialog.classList.add('hidden');
    });

    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.classList.add('hidden');
        }
    });
}

// グローバル関数が確実に公開されていることを確認
// console.log('Map.js: モジュールが読み込まれました');
// console.log('Map.js: window.getCurrentLocation =', typeof window.getCurrentLocation);
// console.log('Map.js: window.fetchNearbyShops =', typeof window.fetchNearbyShops);

// 初期化
function initializeMap() {
    // console.log('Map.js: DOMContentLoaded');
    // console.log('Map.js: APP_BASE_URL =', window.APP_BASE_URL);

    // 位置情報更新ボタン
    const updateBtn = document.getElementById('updateLocationBtn');
    // console.log('Map.js: updateBtn要素 =', updateBtn);

    if (updateBtn) {
        // console.log('Map.js: 位置情報更新ボタンが見つかりました');
        // onclickイベントが既に設定されている場合は、追加のイベントリスナーを設定しない
        // （インラインスクリプトと競合しないように）
        if (!updateBtn.hasAttribute('data-mapjs-listener')) {
            updateBtn.setAttribute('data-mapjs-listener', 'true');
            updateBtn.addEventListener('click', (e) => {
                // console.log('Map.js: 位置情報更新ボタンがクリックされました（addEventListener）');
                // インラインスクリプトのonclickが先に実行される可能性があるため、
                // ここでは何もしない（インラインスクリプトが処理する）
            });
        }
    } else {
        console.error('Map.js: 位置情報更新ボタンが見つかりません');
    }

    // タブ切り替えとダイアログはブレード側の initializeInline() が担当するため、
    // map.js では重複登録しない（setupTabs/setupDialog は呼ばない）
}

// DOMContentLoadedイベントを待つ
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMap);
} else {
    // DOMContentLoadedが既に発火している場合は即座に実行
    initializeMap();
}

