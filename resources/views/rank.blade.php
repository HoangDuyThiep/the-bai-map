<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bảng xếp hạng - The Bai Map</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #eef3f8;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #637083;
            --line: #d9e0ea;
            --accent: #0f766e;
            --shadow: 0 12px 32px rgba(15, 23, 42, .12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .page {
            width: min(920px, 100%);
            margin: 0 auto;
            padding: 18px 14px calc(28px + env(safe-area-inset-bottom));
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 22px;
        }

        a {
            color: var(--accent);
            font-weight: 800;
            text-decoration: none;
        }

        .rank-list {
            display: grid;
            gap: 10px;
        }

        .rank-card {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .rank-number {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            color: #ffffff;
            background: var(--accent);
            font-size: 18px;
            font-weight: 900;
        }

        .member-name {
            margin: 0 0 6px;
            font-weight: 850;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            color: var(--muted);
            font-size: 14px;
        }

        .stat {
            padding: 4px 8px;
            border-radius: 999px;
            background: #eef3f8;
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="top">
            <h1>Bảng xếp hạng</h1>
            <a href="{{ route('map') }}">Về bản đồ</a>
        </div>

        <section class="rank-list" aria-label="Bảng xếp hạng thành viên">
            @forelse ($members as $member)
                <article class="rank-card">
                    <div class="rank-number">{{ $loop->iteration }}</div>
                    <div>
                        <p class="member-name">{{ $member->name }}</p>
                        <div class="stats">
                            <span class="stat">{{ $member->sales_reports_count }} bài đăng</span>
                            <span class="stat">{{ $member->helpful_votes_count }} hữu ích</span>
                        </div>
                    </div>
                </article>
            @empty
                <article class="rank-card">
                    <div class="rank-number">-</div>
                    <div>
                        <p class="member-name">Chưa có thành viên active</p>
                        <div class="stats">
                            <span class="stat">0 bài đăng</span>
                            <span class="stat">0 hữu ích</span>
                        </div>
                    </div>
                </article>
            @endforelse
        </section>
    </main>
</body>
</html>
