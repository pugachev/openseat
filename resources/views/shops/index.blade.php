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
<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">空き状況を確認</h2>
    <p class="text-gray-600">現在の混雑状況を確認できます</p>
</div>

<!-- ボタンエリア -->
<div class="mb-4 flex flex-wrap gap-4">
    <button id="updateLocationBtn"
            onclick="handleLocationUpdate(event); return false;"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-md cursor-pointer">
        📍 位置情報を更新
    </button>
    <button id="openRegisterModalBtn"
            onclick="openRegisterModal(); return false;"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium shadow-md cursor-pointer">
        🏪 店舗を登録
    </button>
</div>
    <p id="locationStatus" class="mt-2 text-sm text-gray-600"></p>
    <p id="debugInfo" class="mt-2 text-xs text-gray-400"></p>

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

                    // 地図タブは既にアクティブのはず（初期表示が地図タブなので）
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
                                        // リスト表示も更新（displayShopsOnMap内で呼ばれるが、念のため）
                                        if (typeof updateShopList === 'function') {
                                            updateShopList(data);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('APIエラー:', error);
                                        statusEl.textContent = '店舗情報の取得に失敗しました';
                                        statusEl.className = 'mt-2 text-sm text-red-600';
                                        // エラー時もリストを空にする
                                        if (typeof updateShopList === 'function') {
                                            updateShopList([]);
                                        }
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
let currentOpenMarker = null; // 現在開いているマーカーを追跡

function initMapInline(lat, lng) {
    // console.log('initMapInline呼び出し: lat=' + lat + ', lng=' + lng);
    return new Promise((resolve, reject) => {
        // Leafletが読み込まれているか確認
        if (typeof L !== 'undefined') {
            // console.log('Leaflet.jsは既に読み込まれています');
            // console.log('Lオブジェクト:', L);
            try {
                createMap(lat, lng);
                // 少し遅延させてからresolve（地図が確実に描画されるまで待つ）
                setTimeout(() => {
                    // console.log('initMapInline resolve');
                    resolve();
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
                resolve();
            };
            leafletJS.onerror = () => {
                console.error('Leaflet.jsの読み込みに失敗しました');
                reject(new Error('Leaflet.jsの読み込みに失敗しました'));
            };
            document.head.appendChild(leafletJS);
        }
    });
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

// リスト表示を更新する関数
function updateShopList(shops) {
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
        // secret_key属性を追加（ステータス更新時に検索用）
        if (shop.secret_key) {
            shopCard.setAttribute('data-secret-key', shop.secret_key);
        }
        shopCard.innerHTML = `
            <div class="p-6">
                <div class="space-y-3">
                    <!-- 店舗名 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">店舗名</label>
                        <p class="text-lg font-semibold text-gray-900">${shop.name || '店舗名未設定'}</p>
                </div>

                    <!-- 業種 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">業種</label>
                        <p class="text-base text-gray-900">${shop.category || 'N/A'}</p>
                </div>

                    <!-- ステータス -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
                        <span class="shop-status-badge inline-block px-3 py-1 rounded-full text-sm font-medium ${statusClass}" data-status="${shop.status}">
                            ${statusLabel}
                        </span>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(shopCard);
    });
}

// リスト表示のステータスを更新する関数（DOM操作のみ、Ajax不要）
function updateShopListStatus(secretKey, newStatus) {
    if (!secretKey) return;
    
    // secret_keyで該当する店舗カードを検索
    const container = document.getElementById('shopListContainer');
    if (!container) return;
    
    const shopCard = container.querySelector(`[data-secret-key="${secretKey}"]`);
    if (!shopCard) return;
    
    // ステータスラベルとクラスを更新
    const statusLabel = newStatus === 0 ? '空き' : newStatus === 1 ? '待ち' : '満席';
    const statusClass = newStatus === 0 
        ? 'bg-green-100 text-green-800' 
        : newStatus === 1 
        ? 'bg-yellow-100 text-yellow-800' 
        : 'bg-red-100 text-red-800';
    
    // ステータスバッジを更新
    const statusBadge = shopCard.querySelector('.shop-status-badge');
    if (statusBadge) {
        statusBadge.textContent = statusLabel;
        statusBadge.className = `shop-status-badge inline-block px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
        statusBadge.setAttribute('data-status', newStatus);
    }
}

// カスタムアイコンの作成（グローバル関数として定義）
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
        // リスト表示も更新
        updateShopList([]);
        return;
    }

    // 店舗をマーカーで表示
    shops.forEach(shop => {
        const marker = L.marker([shop.latitude, shop.longitude], {
            icon: createCustomIcon(shop.status),
        }).addTo(inlineMap);

        // マーカーに店舗データを保存（ステータス更新時に使用）
        marker.shopData = shop;

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

        // ポップアップの内容を作成（クリック可能にする）
        const popupContent = `
            <div class="text-center cursor-pointer" style="min-width: 120px;">
                <strong>${shop.name || '店舗名未設定'}</strong><br>
                ${shop.category ? `<span class="text-xs">${shop.category}</span><br>` : ''}
                <span class="text-sm">${statusLabel}</span><br>
                <span class="text-xs text-blue-600 mt-1 block">タップして詳細を表示</span>
            </div>
        `;
        
        marker.bindPopup(popupContent);

        // マーカークリック時はポップアップのみ表示（モーダルは表示しない）
        marker.on('click', (e) => {
            if (e.originalEvent) {
                e.originalEvent.stopPropagation();
            }
            currentOpenMarker = marker; // 現在開いているマーカーを保存
            marker.openPopup(); // ポップアップのみ開く
        });

        // ポップアップが開いた後にクリックイベントを追加
        marker.on('popupopen', () => {
            // 少し遅延させてからイベントリスナーを追加（DOMが確実に更新されるまで待つ）
            setTimeout(() => {
                const popup = marker.getPopup();
                if (popup) {
                    const popupElement = popup.getElement();
                    if (popupElement) {
                        // ポップアップの閉じるボタン（×）のイベントを制御
                        const closeButton = popupElement.querySelector('.leaflet-popup-close-button');
                        if (closeButton) {
                            // 閉じるボタンのクリックイベントを上書き
                            closeButton.addEventListener('click', (e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                // 閉じるボタンはポップアップを閉じるだけ（モーダルは開かない）
                                marker.closePopup();
                                currentOpenMarker = null; // マーカーをクリア
                            });
                        }
                        
                        // ポップアップのコンテンツ部分をクリック可能にする
                        const popupContent = popupElement.querySelector('.leaflet-popup-content');
                        if (popupContent) {
                            popupContent.style.cursor = 'pointer';
                            
                            // ポップアップコンテンツのクリックイベント
                            const handleContentClick = (e) => {
                                // 閉じるボタンがクリックされた場合は何もしない
                                if (e.target.closest('.leaflet-popup-close-button')) {
                                    return;
                                }
                                e.stopPropagation();
                                e.preventDefault();
                                currentOpenMarker = marker; // 現在開いているマーカーを保存
                                // ポップアップを閉じてからモーダルを開く
                                marker.closePopup();
            showShopDialogInline(shop);
                            };
                            
                            // 既存のイベントリスナーを削除してから追加（重複防止）
                            popupContent.removeEventListener('click', handleContentClick);
                            popupContent.addEventListener('click', handleContentClick);
                        }
                    }
                }
            }, 100);
        });
        
        // ポップアップが閉じた時にマーカーをクリア
        marker.on('popupclose', () => {
            if (currentOpenMarker === marker) {
                // モーダルが開いていない場合のみクリア
                const dialog = document.getElementById('shopDialog');
                if (dialog && dialog.classList.contains('hidden')) {
                    currentOpenMarker = null;
                }
            }
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

    // リスト表示も更新
    updateShopList(shops);
}

function showShopDialogInline(shop) {
    const dialog = document.getElementById('shopDialog');
    const nameEl = document.getElementById('dialogShopName');
    const contentEl = document.getElementById('dialogShopContent');

    if (!dialog || !nameEl || !contentEl) {
        console.error('ダイアログ要素が見つかりません');
        return;
    }

    // 店舗名を編集可能な入力フィールドに変更
    const escapedName = (shop.name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    nameEl.innerHTML = `
        <label for="dialogShopNameInput" class="block text-sm font-medium text-gray-700 mb-2">
            店舗名（任意）
        </label>
        <input type="text" 
               id="dialogShopNameInput" 
               value="${escapedName}" 
               placeholder="店舗名（任意）"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-semibold"
               data-secret-key="${shop.secret_key}">
    `;

    // 更新日時をフォーマット
    let updateTime = '';
    if (shop.updated_at) {
        const date = new Date(shop.updated_at);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        updateTime = `${year}/${month}/${day} ${hours}:${minutes}に更新`;
    }

    contentEl.innerHTML = `
        <div class="space-y-3">
            <!-- 業種表示 -->
            ${shop.category ? `<p class="text-gray-600">
                <span class="font-medium">業種:</span> ${shop.category}
            </p>` : ''}
            
            <!-- ステータスセレクトボックス -->
            <div>
                <label for="dialogStatusSelect" class="block text-sm font-medium text-gray-700 mb-2">
                    ステータス
                </label>
                <select id="dialogStatusSelect" 
                        data-secret-key="${shop.secret_key}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="0" ${shop.status == 0 ? 'selected' : ''}>🟢 空き</option>
                    <option value="1" ${shop.status == 1 ? 'selected' : ''}>🟡 待ち</option>
                    <option value="2" ${shop.status == 2 ? 'selected' : ''}>🔴 満席</option>
                </select>
            </div>
            
            <!-- 住所 -->
            ${shop.address ? `<p class="text-gray-600">
                <span class="font-medium">住所:</span> ${shop.address}
            </p>` : ''}
            
            <!-- 電話番号 -->
            ${shop.phone ? `<p class="text-gray-600">
                <span class="font-medium">電話:</span> ${shop.phone}
            </p>` : ''}
            
            <!-- 距離 -->
            ${shop.distance ? `<p class="text-gray-600">
                <span class="font-medium">距離:</span> ${shop.distance}km
            </p>` : ''}
            
            <!-- 更新日時 -->
            ${updateTime ? `<p class="text-xs text-gray-500 mt-2">${updateTime}</p>` : ''}
        </div>
    `;

    // ステータス変更イベントリスナーを追加
    const statusSelect = document.getElementById('dialogStatusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            updateShopStatus(this.value, this.dataset.secretKey);
        });
    }

    // 店舗名変更イベントリスナーを追加（入力が終わった時、Enterキー、またはフォーカスアウト時）
    const nameInput = document.getElementById('dialogShopNameInput');
    if (nameInput) {
        let nameUpdateTimeout = null;
        const handleNameUpdate = () => {
            const newName = nameInput.value.trim();
            const secretKey = nameInput.dataset.secretKey;
            if (secretKey) {
                updateShopName(newName, secretKey);
            }
        };
        
        nameInput.addEventListener('blur', handleNameUpdate);
        nameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                nameInput.blur(); // フォーカスを外してblurイベントを発火
            }
        });
    }

    dialog.classList.remove('hidden');
    
    // モーダルを開いた時にポップアップを閉じる
    if (currentOpenMarker) {
        try {
            currentOpenMarker.closePopup();
        } catch (e) {
            console.warn('ポップアップを閉じる際にエラーが発生しました:', e);
        }
    }
}

// 店舗ステータス更新関数
function updateShopStatus(status, secretKey) {
    const statusSelect = document.getElementById('dialogStatusSelect');
    const contentEl = document.getElementById('dialogShopContent');
    if (!statusSelect || !secretKey || !contentEl) return;

    const originalValue = statusSelect.dataset.originalValue || status;
    statusSelect.disabled = true;

    // CSRFトークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRFトークンが見つかりません');
        alert('CSRFトークンが見つかりません。ページをリロードしてください。');
        statusSelect.disabled = false;
        return;
    }

    fetch(`/api/shops/${secretKey}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: parseInt(status) }),
    })
    .then(response => {
        // レスポンスがJSONかどうかを確認
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // JSONでない場合（HTMLエラーページなど）はテキストとして読み込む
            return response.text().then(text => {
                throw new Error('サーバーエラーが発生しました: ' + response.status);
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 更新日時を更新
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const updateTimeText = `${year}/${month}/${day} ${hours}:${minutes}に更新`;
            
            // 更新日時を表示
            let updateTimeEl = contentEl.querySelector('.text-xs.text-gray-500');
            if (!updateTimeEl) {
                updateTimeEl = document.createElement('p');
                updateTimeEl.className = 'text-xs text-gray-500 mt-2';
                contentEl.appendChild(updateTimeEl);
            }
            updateTimeEl.textContent = updateTimeText;
            
            statusSelect.disabled = false;
            
            // 地図上のマーカーも更新（必要に応じて）
            if (typeof inlineMarkers !== 'undefined' && inlineMarkers) {
                inlineMarkers.forEach(marker => {
                    const markerShop = marker.shopData;
                    if (markerShop && markerShop.secret_key === secretKey) {
                        markerShop.status = parseInt(status);
                        // マーカーのアイコンを更新
                        marker.setIcon(createCustomIcon(parseInt(status)));
                    }
                });
            }
            
            // リスト表示のステータスも更新（DOM操作のみ、Ajax不要）
            updateShopListStatus(secretKey, parseInt(status));
        } else {
            const errorMessage = data.message || 'ステータスの更新に失敗しました';
            console.error('更新失敗:', data);
            alert(errorMessage);
            statusSelect.value = originalValue;
            statusSelect.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'ステータスの更新中にエラーが発生しました';
        alert(errorMessage);
        statusSelect.value = originalValue;
        statusSelect.disabled = false;
    });
}

// 店舗名更新関数
function updateShopName(name, secretKey) {
    const nameInput = document.getElementById('dialogShopNameInput');
    if (!nameInput || !secretKey) return;

    const originalValue = nameInput.value;
    nameInput.disabled = true;

    // CSRFトークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRFトークンが見つかりません');
        nameInput.disabled = false;
        return;
    }

    fetch(`/api/shops/${secretKey}/name`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ name: name }),
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error('サーバーエラーが発生しました: ' + response.status);
            });
        }
    })
    .then(data => {
        if (data.success) {
            // 更新日時を更新
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const updateTimeText = `${year}/${month}/${day} ${hours}:${minutes}に更新`;
            
            // 更新日時を表示
            const contentEl = document.getElementById('dialogShopContent');
            if (contentEl) {
                let updateTimeEl = contentEl.querySelector('.text-xs.text-gray-500');
                if (!updateTimeEl) {
                    updateTimeEl = document.createElement('p');
                    updateTimeEl.className = 'text-xs text-gray-500 mt-2';
                    contentEl.appendChild(updateTimeEl);
                }
                updateTimeEl.textContent = updateTimeText;
            }
            
            nameInput.disabled = false;
            
            // 地図上のマーカーも更新（必要に応じて）
            if (typeof inlineMarkers !== 'undefined' && inlineMarkers) {
                inlineMarkers.forEach(marker => {
                    const markerShop = marker.shopData;
                    if (markerShop && markerShop.secret_key === secretKey) {
                        markerShop.name = name;
                        // ポップアップを更新
        const statusLabels = {
            0: '空き',
            1: '待ち',
            2: '満席'
        };
                        const statusLabel = statusLabels[markerShop.status] || '不明';
                        marker.setPopupContent(`
                            <div class="text-center">
                                <strong>${name || '店舗名未設定'}</strong><br>
                                ${markerShop.category ? `<span class="text-xs">${markerShop.category}</span><br>` : ''}
                                <span class="text-sm">${statusLabel}</span>
                            </div>
                        `);
                    }
                });
            }
        } else {
            const errorMessage = data.message || '店舗名の更新に失敗しました';
            console.error('更新失敗:', data);
            alert(errorMessage);
            nameInput.value = originalValue;
            nameInput.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || '店舗名の更新中にエラーが発生しました';
        alert(errorMessage);
        nameInput.value = originalValue;
        nameInput.disabled = false;
    });
}

// 現在位置を取得して地図をリフレッシュする関数（店舗登録後用：現在地マーカーは表示しない）
function refreshMapWithCurrentLocation() {
    if (!navigator.geolocation) {
        console.warn('このブラウザは位置情報をサポートしていません');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // 地図が既に初期化されている場合は、位置を更新
            if (inlineMap) {
                // 地図の中心を現在位置に移動
                inlineMap.setView([lat, lng], 16);
                
                // 既存のマーカーを削除
                inlineMarkers.forEach(marker => inlineMap.removeLayer(marker));
                inlineMarkers = [];
                
                // 現在地マーカーは追加しない（店舗のピンのみ表示）
            } else {
                // 地図が初期化されていない場合は初期化
                initMapInline(lat, lng).catch(error => {
                    console.error('地図初期化エラー:', error);
                });
            }
            
            // 店舗データを取得して表示
            const baseUrl = window.APP_BASE_URL || '';
            const apiUrl = `${baseUrl}/api/shops/nearby`;
            
            fetch(`${apiUrl}?latitude=${lat}&longitude=${lng}`)
                .then(response => response.json())
                .then(data => {
                    displayShopsOnMap(data, lat, lng);
                })
                .catch(error => {
                    console.error('APIエラー:', error);
                });
        },
        function(error) {
            console.warn('位置情報の取得に失敗しました:', error);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
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

    // 初期状態を設定（地図タブがアクティブ）
    mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
    mapTab.classList.remove('border-transparent', 'text-gray-500');
    listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
    listTab.classList.add('border-transparent', 'text-gray-500');
    listView.classList.add('hidden');
    mapView.classList.remove('hidden');

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
    
    // 画面初期表示時に現在位置を自動取得して地図に表示
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // 地図を初期化
                initMapInline(lat, lng).then(() => {
                    // 店舗データを取得して表示
                    const baseUrl = window.APP_BASE_URL || '';
                    const apiUrl = `${baseUrl}/api/shops/nearby`;
                    
                    fetch(`${apiUrl}?latitude=${lat}&longitude=${lng}`)
                        .then(response => response.json())
                        .then(data => {
                            displayShopsOnMap(data, lat, lng);
                        })
                        .catch(error => {
                            console.error('APIエラー:', error);
                        });
                }).catch(error => {
                    console.error('地図初期化エラー:', error);
                });
            },
            function(error) {
                // 位置情報取得失敗時はデフォルト位置（東京）で初期化
                console.warn('位置情報の取得に失敗しました。デフォルト位置で地図を表示します。');
                const defaultLat = 35.6812;
                const defaultLng = 139.7671;
                initMapInline(defaultLat, defaultLng).catch(error => {
                    console.error('地図初期化エラー:', error);
                });
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        // Geolocation APIがサポートされていない場合はデフォルト位置で初期化
        const defaultLat = 35.6812;
        const defaultLng = 139.7671;
        initMapInline(defaultLat, defaultLng).catch(error => {
            console.error('地図初期化エラー:', error);
        });
    }
}

// モーダルを閉じる共通関数
function closeShopDialog() {
    const dialog = document.getElementById('shopDialog');
    if (dialog) {
        dialog.classList.add('hidden');
    }
    // ポップアップも確実に閉じる
    if (currentOpenMarker) {
        // 複数の方法でポップアップを閉じる試み
        try {
            // 方法1: closePopup()を直接呼ぶ
            if (currentOpenMarker.isPopupOpen && currentOpenMarker.isPopupOpen()) {
                currentOpenMarker.closePopup();
            } else {
                currentOpenMarker.closePopup();
            }
        } catch (e) {
            console.warn('closePopup()でエラー:', e);
        }
        
        try {
            // 方法2: 地図からポップアップを閉じる
            if (inlineMap) {
                inlineMap.closePopup();
            }
        } catch (e) {
            console.warn('map.closePopup()でエラー:', e);
        }
        
        // 少し遅延させて再度確認（確実に閉じるため）
    setTimeout(() => {
            if (currentOpenMarker) {
                try {
                    if (currentOpenMarker.isPopupOpen && currentOpenMarker.isPopupOpen()) {
                        currentOpenMarker.closePopup();
                    }
                } catch (e) {
                    // エラーは無視
                }
            }
        }, 50);
        
        currentOpenMarker = null;
    }
}

// ダイアログの初期化（閉じるボタンのイベント設定など）
function setupDialogInline() {
    const dialog = document.getElementById('shopDialog');
    const closeBtn = document.getElementById('closeDialogBtn');
    if (!dialog || !closeBtn) return;
    
    closeBtn.addEventListener('click', () => {
        closeShopDialog();
    });
    
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            closeShopDialog();
        }
    });
    
    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const dialog = document.getElementById('shopDialog');
            if (dialog && !dialog.classList.contains('hidden')) {
                closeShopDialog();
            }
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
</style>
<div class="mb-6 border-b border-gray-200">
    <nav class="flex space-x-8">
        <button id="mapTab" class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600">
            地図表示
        </button>
        <button id="listTab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700">
            リスト表示
        </button>
    </nav>
</div>

<!-- 地図表示エリア -->
<div id="mapView" class="tab-content">
    <div id="map" class="w-full h-[600px] rounded-lg shadow-md border border-gray-200"></div>
</div>

<!-- リスト表示エリア -->
<div id="listView" class="tab-content hidden">
    <div id="shopListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 col-span-full">
            <p class="text-gray-500">位置情報を更新すると、近くの店舗が表示されます</p>
        </div>
    </div>
</div>

<!-- 店舗情報ダイアログ -->
<div id="shopDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6" style="z-index: 10000;">
        <div class="flex justify-between items-start mb-4">
            <div id="dialogShopName" class="flex-1 mr-4">
                <!-- 店舗名がここに動的に挿入されます -->
            </div>
            <button id="closeDialogBtn" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="dialogShopContent" class="space-y-3">
            <!-- 店舗情報がここに動的に挿入されます -->
        </div>
        </div>
    </div>

<!-- 店舗登録モーダル -->
<div id="registerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto" style="z-index: 10000;">
        <!-- ヘッダー -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h3 class="text-2xl font-semibold text-gray-900">店舗を登録</h3>
            <button id="closeRegisterModalBtn" 
                    onclick="closeRegisterModal(); return false;"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
</div>

        <!-- フォーム本体 -->
        <form id="registerShopForm" action="{{ route('shops.register.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            <!-- 業種 -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                    業種 <span class="text-red-500">*</span>
                </label>
                <select id="category" 
                        name="category" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="">選択してください</option>
                    <option value="ヘアーサロン">ヘアーサロン</option>
                    <option value="飲食">飲食</option>
                    <option value="ボディケア">ボディケア</option>
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 店舗名 -->
            <div>
                <label for="shopName" class="block text-sm font-medium text-gray-700 mb-2">
                    店舗名（任意）
                </label>
                <input type="text" 
                       id="shopName" 
                       name="name" 
                       value="{{ old('name') }}"
                       placeholder="例: 〇〇サロン"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 位置情報（hidden） -->
            <input type="hidden" id="latitude" name="latitude" value="">
            <input type="hidden" id="longitude" name="longitude" value="">

            <!-- 位置情報表示エリア -->
            <div>
                <p id="locationStatusForRegister" class="mt-2 text-sm text-gray-600"></p>
                <p class="mt-2 text-xs text-gray-500">※「登録する」ボタンを押すと自動的に現在位置を取得して登録します</p>
            </div>

            <!-- エラーメッセージ表示エリア -->
            <div id="registerError" class="hidden text-sm text-red-600"></div>

            <!-- フッター -->
            <div class="flex gap-4 pt-4">
                <button type="button" 
                        onclick="closeRegisterModal(); return false;"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    キャンセル
                </button>
                <button type="submit" 
                        id="submitRegisterBtn"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    登録する
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// モーダルの開閉制御
function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // 背景のスクロールを無効化
        
        // 地図ビューが非表示の場合は表示する（モーダル表示時にも地図を表示）
        const mapView = document.getElementById('mapView');
        const listView = document.getElementById('listView');
        const mapTab = document.getElementById('mapTab');
        const listTab = document.getElementById('listTab');
        
        if (mapView && mapView.classList.contains('hidden')) {
            // 地図タブをアクティブにする
            if (mapTab) {
                mapTab.classList.add('active', 'border-blue-500', 'text-blue-600');
                mapTab.classList.remove('border-transparent', 'text-gray-500');
            }
            if (listTab) {
                listTab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                listTab.classList.add('border-transparent', 'text-gray-500');
            }
            // 地図ビューを表示
            mapView.classList.remove('hidden');
            mapView.style.display = 'block';
            // リストビューを非表示
            if (listView) {
                listView.classList.add('hidden');
                listView.style.display = 'none';
            }
            
            // 地図のサイズを再計算（少し遅延させて確実に）
            setTimeout(() => {
                if (typeof inlineMap !== 'undefined' && inlineMap) {
                    inlineMap.invalidateSize();
                }
            }, 100);
        }
    }
}

function closeRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // 背景のスクロールを有効化
        // フォームをリセット
        const form = document.getElementById('registerShopForm');
        if (form) {
            form.reset();
        }
        // エラーメッセージをクリア
        const errorDiv = document.getElementById('registerError');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
            errorDiv.textContent = '';
        }
        // ステータスメッセージをクリア
        const statusEl = document.getElementById('locationStatusForRegister');
        if (statusEl) {
            statusEl.textContent = '';
        }
        // 位置情報をクリア
        document.getElementById('latitude').value = '';
        document.getElementById('longitude').value = '';
        // 送信ボタンを無効化
        const submitBtn = document.getElementById('submitRegisterBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    }
}

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('registerModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeRegisterModal();
        }
    }
});

// 背景クリックでモーダルを閉じる
document.getElementById('registerModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeRegisterModal();
    }
});


// フォーム送信処理（Ajax対応）
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerShopForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitRegisterBtn');
            const errorDiv = document.getElementById('registerError');
            const statusEl = document.getElementById('locationStatusForRegister');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const latitude = latitudeInput.value;
            const longitude = longitudeInput.value;

            // 送信ボタンを無効化
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '位置情報を取得中...';
            }

            // エラーメッセージをクリア
            if (errorDiv) {
                errorDiv.classList.add('hidden');
                errorDiv.textContent = '';
            }

            // 位置情報が取得されているか確認
            if (!latitude || !longitude) {
                // 位置情報が取得されていない場合は自動的に取得
                if (statusEl) {
                    statusEl.textContent = '位置情報を取得中...';
                    statusEl.className = 'mt-2 text-sm text-blue-600';
                }

                if (!navigator.geolocation) {
                    if (errorDiv) {
                        errorDiv.textContent = 'このブラウザは位置情報をサポートしていません';
                        errorDiv.classList.remove('hidden');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '登録する';
                    }
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        latitudeInput.value = lat;
                        longitudeInput.value = lng;

                        if (statusEl) {
                            statusEl.textContent = `位置情報を取得しました（緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}）`;
                            statusEl.className = 'mt-2 text-sm text-green-600';
                        }

                        // 位置情報を取得したらフォームを送信
                        submitForm();
                    },
                    function(error) {
                        let message = '位置情報の取得に失敗しました';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                message = '位置情報の使用が拒否されました。ブラウザの設定を確認してください。';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = '位置情報が利用できません';
                                break;
                            case error.TIMEOUT:
                                message = '位置情報の取得がタイムアウトしました';
                                break;
                        }
                        if (statusEl) {
                            statusEl.textContent = message;
                            statusEl.className = 'mt-2 text-sm text-red-600';
                        }
                        if (errorDiv) {
                            errorDiv.textContent = message;
                            errorDiv.classList.remove('hidden');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = '登録する';
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                // 位置情報が既に取得されている場合はそのまま送信
                submitForm();
            }

            // フォーム送信関数
            function submitForm() {
                if (submitBtn) {
                    submitBtn.textContent = '登録中...';
                }

                // CSRFトークンを取得
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // FormDataを作成
                const formData = new FormData(form);

                // Ajaxで送信
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                })
                .then(response => {
                    // レスポンスがJSONかどうかを確認
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // JSONでない場合（HTMLエラーページなど）はテキストとして読み込む
                        return response.text().then(text => {
                            throw new Error('サーバーエラーが発生しました');
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        // 成功メッセージを表示
                        alert('店舗を登録しました！');
                        // モーダルを閉じる
                        closeRegisterModal();
                        // 現在位置を取得して地図をリフレッシュ
                        refreshMapWithCurrentLocation();
                    } else {
                        // エラーメッセージを表示
                        if (errorDiv) {
                            errorDiv.textContent = data.message || '登録に失敗しました';
                            errorDiv.classList.remove('hidden');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = '登録する';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (errorDiv) {
                        errorDiv.textContent = error.message || '登録中にエラーが発生しました。データベースエラーの可能性があります。';
                        errorDiv.classList.remove('hidden');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '登録する';
                    }
                });
            }
        });
    }
});
</script>
@endsection

