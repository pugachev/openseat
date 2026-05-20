<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ピンが投稿されました</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
        .header { background: #f59e0b; padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 20px; color: #ffffff; font-weight: 700; }
        .header p { margin: 6px 0 0; font-size: 13px; color: rgba(255,255,255,0.85); }
        .body { padding: 28px 32px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .pin-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 18px 20px; margin: 20px 0; }
        .pin-box .label { font-size: 12px; color: #92400e; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .pin-box .comment { font-size: 15px; color: #1f2937; margin: 0; }
        .btn-wrap { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; background: #f59e0b; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-size: 16px; font-weight: 700; }
        .hint { font-size: 12px; color: #9ca3af; text-align: center; margin: 8px 0 0; }
        .url-text { word-break: break-all; font-size: 12px; color: #6b7280; margin-top: 6px; }
        .footer { background: #f3f4f6; padding: 16px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>📍 ピンが投稿されました</h1>
            <p>{{ config('app.name') }}</p>
        </div>
        <div class="body">
            <p>ピンの投稿が完了しました。以下のリンクからOpenStreetMap上でピンの場所を確認できます。</p>

            @if($pin->comment)
            <div class="pin-box">
                <p class="label">コメント</p>
                <p class="comment">{{ $pin->comment }}</p>
            </div>
            @endif

            <div class="btn-wrap">
                <a href="{{ $pinUrl }}" class="btn">📍 ピンを地図で確認する</a>
            </div>
            <p class="hint">ボタンが開かない場合は以下のURLをコピーしてブラウザに貼り付けてください</p>
            <p class="url-text">{{ $pinUrl }}</p>
        </div>
        <div class="footer">
            <p>このメールは {{ config('app.name') }} から自動送信されました。</p>
        </div>
    </div>
</body>
</html>
