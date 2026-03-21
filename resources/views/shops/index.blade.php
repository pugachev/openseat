@extends('layouts.app')

@section('title', '店舗一覧 - Open Seat')

@section('content')
<script>
    // ベースURLを設定（/public/を含む環境に対応）
    window.APP_BASE_URL = '{{ url("/") }}';
    // console.log('APP_BASE_URL:', window.APP_BASE_URL);
    // console.log('現在のURL:', window.location.href);

    // 簡単なテスト
    // console.log('スクリプトが読み込まれました');
</script>
<div class="shops-layout">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">空き状況を確認</h2>
    <p class="text-gray-600">現在の混雑状況を確認できます</p>
</div>

<!-- 位置情報更新ボタン -->
<div class="shops-layout pt-0 pb-3">
    <button id="updateLocationBtn"
            onclick="handleLocationUpdate(event); return false;"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-md cursor-pointer">
        📍 位置情報を更新
    </button>
    <p id="locationStatus" class="mt-2 text-sm text-gray-600"></p>
    <p id="debugInfo" class="mt-2 text-xs text-gray-400"></p>
</div>
    
<script>
// インラインで位置情報更新を処理（JavaScriptが読み込まれていない場合のフォールバック）
function handleLocationUpdate(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // console.log('handleLocationUpdate: ボタンがクリックされました');

    const statusEl = document.getElementById('locationStatus');
    const debugEl = document.getElementById('debugInfo');

    // if (debugEl) {
    //     debugEl.textContent = 'ボタンがクリックされました - ' + new Date().toLocaleTimeString();
    //     debugEl.className = 'mt-2 text-xs text-green-600';
    // }

    if (!statusEl) {
        alert('エラー: ステータス要素が見つかりません');
        return;
    }

    statusEl.textContent = '処理を開始しました...';
    statusEl.className = 'mt-2 text-sm text-blue-600';

    // map.jsが読み込まれるまで少し待つ
    let retryCount = 0;
    const maxRetries = 10;

    function tryGetLocation() {
        // グローバル関数が利用可能か確認
        if (typeof window.getCurrentLocation === 'function') {
            // console.log('handleLocationUpdate: getCurrentLocation関数を使用');
            window.getCurrentLocation().catch(error => {
                console.error('位置情報取得エラー:', error);
                statusEl.textContent = '位置情報の取得に失敗しました';
                statusEl.className = 'mt-2 text-sm text-red-600';
            });
        } else if (retryCount < maxRetries) {
            // map.jsがまだ読み込まれていない場合は少し待って再試行
            retryCount++;
            // console.log(`handleLocationUpdate: getCurrentLocation関数を待機中... (${retryCount}/${maxRetries})`);
            setTimeout(tryGetLocation, 200);
        } else {
            // console.log('handleLocationUpdate: map.jsが読み込まれていないため、直接位置情報を取得');
            // 直接位置情報を取得
            if (!navigator.geolocation) {
                statusEl.textContent = 'このブラウザは位置情報をサポートしていません';
                statusEl.className = 'mt-2 text-sm text-red-600';
                return;
            }

            statusEl.textContent = '位置情報を取得中...';
            statusEl.className = 'mt-2 text-sm text-blue-600';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    statusEl.textContent = `位置情報を取得しました（緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}）`;
                    statusEl.className = 'mt-2 text-sm text-green-600';

                    // 地図タブに切り替え
                    const mapTab = document.getElementById('mapTab');
                    const listTab = document.getElementById('listTab');
                    const listView = document.getElementById('listView');
                    const mapView = document.getElementById('mapView');

                    if (mapTab && mapView) {
                        // 地図タブをアクティブにする
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

                        // 地図要素が表示されるまで少し待つ
                        setTimeout(() => {
                            // 地図を初期化・表示
                            // console.log('地図初期化を開始します...');
                            initMapInline(lat, lng).then(() => {
                                // console.log('地図初期化が完了しました');
                                // 店舗データを取得して表示
                                const baseUrl = window.APP_BASE_URL || '';
                                const apiUrl = `${baseUrl}/api/shops/nearby`;
                                // console.log('API URL:', apiUrl);

                                fetch(`${apiUrl}?latitude=${lat}&longitude=${lng}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        // console.log('店舗データ:', data);
                                        displayShopsOnMap(data, lat, lng);
                                    })
                                    .catch(error => {
                                        console.error('APIエラー:', error);
                                        statusEl.textContent = '店舗情報の取得に失敗しました';
                                        statusEl.className = 'mt-2 text-sm text-red-600';
                                    });
                            }).catch(error => {
                                console.error('地図初期化エラー:', error);
                                statusEl.textContent = '地図の表示に失敗しました: ' + error.message;
                                statusEl.className = 'mt-2 text-sm text-red-600';
                            });
                        }, 200);
                    } else {
                        console.error('地図タブまたは地図ビュー要素が見つかりません');
                    }
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
                }
            );
        }
    }

    // 初回試行
    tryGetLocation();
}

// 地図を初期化（インライン版）
let inlineMap = null;
let inlineMarkers = [];
let inlineMapInitializationPromise = null;

function initMapInline(lat, lng) {
    // console.log('initMapInline呼び出し: lat=' + lat + ', lng=' + lng);
    if (inlineMap) {
        inlineMap.setView([lat, lng], 16);
        setTimeout(() => {
            if (inlineMap) {
                inlineMap.invalidateSize();
            }
        }, 100);
        return Promise.resolve(inlineMap);
    }

    if (inlineMapInitializationPromise) {
        return inlineMapInitializationPromise;
    }

    inlineMapInitializationPromise = new Promise((resolve, reject) => {
        // Leafletが読み込まれているか確認
        if (typeof L !== 'undefined') {
            // console.log('Leaflet.jsは既に読み込まれています');
            // console.log('Lオブジェクト:', L);
            try {
                createMap(lat, lng);
                // 少し遅延させてからresolve（地図が確実に描画されるまで待つ）
                setTimeout(() => {
                    // console.log('initMapInline resolve');
                    resolve(inlineMap);
                }, 500);
            } catch (error) {
                console.error('createMap呼び出しエラー:', error);
                reject(error);
            }
        } else {
            // console.log('Leaflet.jsをCDNから読み込みます');
            // Leaflet.jsをCDNから読み込む
            const leafletCSS = document.createElement('link');
            leafletCSS.rel = 'stylesheet';
            leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            leafletCSS.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            leafletCSS.crossOrigin = '';
            document.head.appendChild(leafletCSS);

            const leafletJS = document.createElement('script');
            leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            leafletJS.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            leafletJS.crossOrigin = '';
            leafletJS.onload = () => {
                // console.log('Leaflet.jsの読み込みが完了しました');
                // アイコン設定
                delete L.Icon.Default.prototype._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
                    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                });
                createMap(lat, lng);
                resolve(inlineMap);
            };
            leafletJS.onerror = () => {
                console.error('Leaflet.jsの読み込みに失敗しました');
                reject(new Error('Leaflet.jsの読み込みに失敗しました'));
            };
            document.head.appendChild(leafletJS);
        }
    }).finally(() => {
        inlineMapInitializationPromise = null;
    });

    return inlineMapInitializationPromise;
}

function createMap(lat, lng) {
    // console.log('createMap開始: lat=' + lat + ', lng=' + lng);
    const mapElement = document.getElementById('map');
    // console.log('mapElement:', mapElement);

    if (!mapElement) {
        console.error('地図要素が見つかりません');
        alert('地図要素が見つかりません。ID="map"の要素が存在するか確認してください。');
        return;
    }

    // 地図要素の親要素を確認
    const mapView = document.getElementById('mapView');
    // console.log('mapView要素:', mapView);
    if (mapView) {
        // console.log('mapViewのクラス:', mapView.className);
        // console.log('mapViewがhiddenか:', mapView.classList.contains('hidden'));
        // hiddenクラスを確実に削除
        mapView.classList.remove('hidden');
        mapView.style.display = 'block';
    }

    // 地図要素のスタイルを確認・設定
    // console.log('地図要素のサイズ（初期）:', mapElement.offsetWidth, 'x', mapElement.offsetHeight);
    // console.log('地図要素のスタイル:', window.getComputedStyle(mapElement).display);
    // console.log('地図要素の高さ（computed）:', window.getComputedStyle(mapElement).height);

    // 地図要素に明示的に高さを設定
    if (mapElement.offsetHeight < 100) {
        // console.log('地図要素の高さが不足しているため、明示的に設定します');
        mapElement.style.height = '600px';
        mapElement.style.minHeight = '600px';
    }

    // console.log('地図要素のサイズ（設定後）:', mapElement.offsetWidth, 'x', mapElement.offsetHeight);

    // 既存の地図を削除
    if (inlineMap) {
        // console.log('既存の地図を削除します');
        inlineMap.remove();
        inlineMap = null;
    }

    // Leafletの内部状態がDOM側に残っている場合の二重初期化を防ぐ
    if (mapElement._leaflet_id) {
        delete mapElement._leaflet_id;
    }
    mapElement.innerHTML = '';

    try {
        // console.log('地図を初期化します...');
        // 地図を初期化
        inlineMap = L.map('map').setView([lat, lng], 16);
        // console.log('地図オブジェクトが作成されました:', inlineMap);

        // タイルレイヤーを追加
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(inlineMap);
        // console.log('タイルレイヤーを追加しました');

        // 現在地マーカーを追加
        const userMarker = L.marker([lat, lng], {
            icon: L.icon({
                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
            }),
        }).addTo(inlineMap);
        userMarker.bindPopup('現在地').openPopup();
        // console.log('現在地マーカーを追加しました');

        // 地図のサイズを再計算（少し遅延させて確実に）
        setTimeout(() => {
            if (inlineMap) {
                // console.log('地図のサイズを再計算します');
                inlineMap.invalidateSize();
                // console.log('地図のサイズ再計算完了');
            }
        }, 300);

        // console.log('createMap完了');
    } catch (error) {
        console.error('地図作成エラー:', error);
        alert('地図の作成に失敗しました: ' + error.message);
    }
}

function displayShopsOnMap(shops, userLat, userLng) {
    if (!inlineMap) {
        console.error('地図が初期化されていません');
        return;
    }

    // 既存のマーカーを削除
    inlineMarkers.forEach(marker => inlineMap.removeLayer(marker));
    inlineMarkers = [];

    if (shops.length === 0) {
        alert('近くに店舗が見つかりませんでした');
        return;
    }

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

    // 店舗をマーカーで表示
    shops.forEach(shop => {
        const marker = L.marker([shop.latitude, shop.longitude], {
            icon: createCustomIcon(shop.status),
        }).addTo(inlineMap);

        // ステータスラベルの取得
        let statusLabel = shop.status_label;
        if (!statusLabel) {
            const statusLabels = {
                0: '空き',
                1: '待ち',
                2: '満席'
            };
            statusLabel = statusLabels[shop.status] || '不明';
        }

        marker.bindPopup(`
            <div class="text-center">
                <strong>${shop.name}</strong><br>
                <span class="text-sm">${statusLabel}</span>
            </div>
        `);

        // クリックイベント
        marker.on('click', () => {
            showShopDialogInline(shop);
        });

        inlineMarkers.push(marker);
    });

    // すべてのマーカーが表示されるように地図を調整
    if (inlineMarkers.length > 0) {
        const bounds = L.latLngBounds(inlineMarkers.map(m => m.getLatLng()));
        bounds.extend([userLat, userLng]);
        inlineMap.fitBounds(bounds.pad(0.2), {
            maxZoom: 16
        });
    }
}

function showShopDialogInline(shop) {
    const dialog = document.getElementById('shopDialog');
    const nameEl = document.getElementById('dialogShopName');
    const contentEl = document.getElementById('dialogShopContent');
    const phoneEl = document.getElementById('dialogShopPhone');

    if (!dialog || !nameEl || !contentEl || !phoneEl) {
        console.error('ダイアログ要素が見つかりません');
        return;
    }

    nameEl.textContent = shop.name;

    const statusColors = {
        0: 'bg-green-100 text-green-800',
        1: 'bg-yellow-100 text-yellow-800',
        2: 'bg-red-100 text-red-800',
    };

    // ステータスラベルの取得（APIから来ない場合は計算）
    let statusLabel = shop.status_label;
    if (!statusLabel) {
        const statusLabels = {
            0: '空き',
            1: '待ち',
            2: '満席'
        };
        statusLabel = statusLabels[shop.status] || '不明';
    }

    contentEl.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="px-3 py-1 rounded-full text-sm font-medium ${statusColors[shop.status] || 'bg-gray-100 text-gray-800'}">
                ${statusLabel}
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

// タブ切り替え機能
function setupTabsInline() {
    const listTab = document.getElementById('listTab');
    const mapTab = document.getElementById('mapTab');
    const listView = document.getElementById('listView');
    const mapView = document.getElementById('mapView');

    if (!listTab || !mapTab || !listView || !mapView) {
        console.error('タブ要素が見つかりません');
        return;
    }

    listTab.addEventListener('click', () => {
        // console.log('リストタブがクリックされました');
        listTab.classList.add('active', 'border-blue-500', 'text-blue-600');
        listTab.classList.remove('border-transparent', 'text-gray-500');
        mapTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
        mapTab.classList.add('border-transparent', 'text-gray-500');
        listView.classList.remove('hidden');
        mapView.classList.add('hidden');
        // 地図を確実に非表示にする
        mapView.style.display = 'none';
        listView.style.display = 'block';

        // 開いているダイアログを閉じる
        const dialog = document.getElementById('shopDialog');
        if (dialog && !dialog.classList.contains('hidden')) {
            dialog.classList.add('hidden');
        }
    });

    mapTab.addEventListener('click', () => {
        // console.log('地図タブがクリックされました'); // デバッグ用
        mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
        mapTab.classList.remove('border-transparent', 'text-gray-500');
        listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
        listTab.classList.add('border-transparent', 'text-gray-500');
        listView.classList.add('hidden');
        mapView.classList.remove('hidden');
        // リストを確実に非表示にする
        listView.style.display = 'none';
        mapView.style.display = 'block';

        // 地図のサイズを再計算
        if (inlineMap) {
            setTimeout(() => {
                inlineMap.invalidateSize();
            }, 100);
        }
    });
}

// ページ読み込み時にタブ切り替えとダイアログを設定
function initializeInline() {
    setupTabsInline();
    setupDialogInline();
}

// ダイアログの初期化（閉じるボタンのイベント設定など）
function setupDialogInline() {
    const dialog = document.getElementById('shopDialog');
    const closeBtn = document.getElementById('closeDialogBtn');
    if (!dialog || !closeBtn) return;
    closeBtn.addEventListener('click', () => {
        dialog.classList.add('hidden');
    });
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.classList.add('hidden');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInline);
} else {
    initializeInline();
}
</script>

<!-- タブ切り替え -->
<style>
/* タブ切り替えのスタイル（CSSが読み込まれない場合のフォールバック） */
.tab-button {
    padding: 1rem 0.25rem;
    border-bottom-width: 2px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}
.tab-button.active {
    border-bottom-color: #3b82f6;
    color: #2563eb;
}
.tab-button:not(.active) {
    border-bottom-color: transparent;
    color: #6b7280;
}
.tab-button:not(.active):hover {
    color: #374151;
}
.tab-content.hidden {
    display: none !important;
}

.shops-layout {
    width: 100%;
    margin: 0 auto;
    /* padding: 1.5rem 1rem 2rem; */
}

#map {
    height: 480px;
}

#mapView {
    margin-bottom: 2rem;
}

@media (min-width: 640px) {
    #map {
        height: 680px;
    }
}

@media (min-width: 1024px) {
    #map {
        height: 760px;
    }

    #mapView {
        margin-bottom: 3rem;
    }
}

@media (min-width: 1280px) {
    .shops-layout {
        width: 80vw;
        max-width: 80vw;
    }
}
</style>
<div class="shops-layout pt-2">
<div class="mb-4 border-b border-gray-200">
    <nav class="flex space-x-8">
        <button id="listTab" class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600">
            リスト表示
        </button>
        <button id="mapTab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700">
            地図表示
        </button>
    </nav>
</div>

<!-- リスト表示エリア -->
<div id="listView" class="tab-content">
    @if($shops->isEmpty())
        <div class="text-center py-12">
            <p class="text-gray-500">登録されている店舗がありません</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($shops as $shop)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">{{ $shop->name }}</h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($shop->status === 0) bg-green-100 text-green-800
                                @elseif($shop->status === 1) bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ $shop->status_label }}
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <p class="text-gray-600 text-sm">
                                <span class="font-medium">住所:</span> {{ $shop->address }}
                            </p>
                            <p class="text-gray-600 text-sm">
                                <span class="font-medium">電話:</span> {{ $shop->phone }}
                            </p>
                        </div>

                        <a href="tel:{{ $shop->phone }}"
                           class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <span class="mr-2">📞</span>
                            電話する
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- 地図表示エリア -->
<div id="mapView" class="tab-content hidden">
    <div id="map" class="w-full rounded-lg shadow-md border border-gray-200"></div>
</div>

<!-- 店舗情報ダイアログ -->
<div id="shopDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <div class="flex justify-between items-start mb-4">
            <h3 id="dialogShopName" class="text-2xl font-semibold text-gray-900"></h3>
            <button id="closeDialogBtn" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="dialogShopContent" class="space-y-3">
            <!-- 店舗情報がここに動的に挿入されます -->
        </div>
        <div class="mt-6">
            <a id="dialogShopPhone" href="#"
               class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <span class="mr-2">📞</span>
                電話する
            </a>
        </div>
    </div>
</div>
</div>
@endsection

