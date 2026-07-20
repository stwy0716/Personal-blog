    <!-- Music Player -->
    <?php
    $musicFile = __DIR__ . '/../data/music.json';
    $musicList = readJsonFile($musicFile);
    $musicShuffle = !empty($settings['music_shuffle']);
    $musicLoop = !empty($settings['music_loop']);
    // Filter to only enabled tracks
    $enabledTracks = array_filter($musicList, function($m) {
        return !isset($m['enabled']) || $m['enabled'] !== false;
    });
    $enabledTracks = array_values($enabledTracks);
    $activeTrack = null;
    foreach ($enabledTracks as $m) {
        if (!empty($m['active'])) { $activeTrack = $m; break; }
    }
    if (!$activeTrack && !empty($enabledTracks)) $activeTrack = $enabledTracks[0];
    ?>
    <?php if (!empty($enabledTracks) && $footerMusicPlayer): ?>
    <div class="max-w-6xl mx-auto px-6 mt-16 mb-8">
        <div class="music-player rounded-3xl p-5 text-white shadow-xl" id="music-player-container">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center gap-x-4 min-w-0 flex-1">
                    <button id="music-play-btn" onclick="toggleMusic()"
                            class="w-12 h-12 flex-shrink-0 flex items-center justify-center bg-white/90 hover:bg-white text-gray-900 rounded-2xl transition-all active:scale-95 shadow-inner">
                        <i class="fa-solid fa-play ml-0.5 text-xl" id="music-play-icon"></i>
                    </button>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-x-3 text-xs text-white/70 mb-1">
                            <span id="music-current-time">0:00</span>
                            <div class="flex-1 h-1.5 bg-white/30 rounded-full overflow-hidden cursor-pointer" id="music-progress-bar" onclick="seekMusic(event)">
                                <div class="h-full w-0 bg-white rounded-full transition-all" id="music-progress"></div>
                            </div>
                            <span id="music-duration">0:00</span>
                        </div>
                        <div class="text-sm font-medium truncate">
                            <span id="music-status" class="text-white/70 mr-1 hidden"><?= sanitizeHtml($i18n['footer']['playing'] ?? '正在播放') ?></span>
                            <span id="music-title"><?= sanitizeHtml($activeTrack['title'] ?? ($i18n['footer']['no_track'] ?? '暂无曲目')) ?></span>
                            <span class="text-white/50" id="music-artist"> - <?= sanitizeHtml($activeTrack['artist'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-x-3">
                    <div class="flex items-center gap-x-2">
                        <i class="fa-solid fa-volume-high text-white/60 text-sm"></i>
                        <input type="range" id="music-volume" min="0" max="100" value="60" class="w-20 h-1 accent-white" onchange="setVolume(this.value)">
                    </div>
                    <?php if (count($enabledTracks) > 1): ?>
                    <button onclick="nextTrack()" class="w-9 h-9 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-xl transition-all">
                        <i class="fa-solid fa-forward text-sm"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <audio id="music-audio" preload="auto">
        <?php if ($activeTrack): ?>
        <source src="<?= sanitizeHtml($activeTrack['file']) ?>" type="audio/mpeg">
        <?php endif; ?>
    </audio>

    <script>
    const musicTracks = <?= json_encode(array_map(function($m) {
        return ['title' => $m['title'], 'artist' => $m['artist'] ?? '', 'file' => $m['file']];
    }, $enabledTracks)) ?>;
    let currentTrackIndex = 0;
    const audio = document.getElementById('music-audio');
    const playIcon = document.getElementById('music-play-icon');
    const progressBar = document.getElementById('music-progress');
    const currentTimeEl = document.getElementById('music-current-time');
    const durationEl = document.getElementById('music-duration');
    const titleEl = document.getElementById('music-title');
    const artistEl = document.getElementById('music-artist');

    // Find active track index among enabled tracks
    <?php foreach ($enabledTracks as $i => $m): ?>
        <?php if (!empty($m['active'])): ?>
        currentTrackIndex = <?= $i ?>;
        <?php endif; ?>
    <?php endforeach; ?>

    audio.volume = 0.6;

    function formatTime(s) {
        if (isNaN(s)) return '0:00';
        const m = Math.floor(s / 60);
        const sec = Math.floor(s % 60);
        return m + ':' + sec.toString().padStart(2, '0');
    }

    audio.addEventListener('timeupdate', function() {
        if (audio.duration) {
            progressBar.style.width = (audio.currentTime / audio.duration * 100) + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
    });

    audio.addEventListener('loadedmetadata', function() {
        durationEl.textContent = formatTime(audio.duration);
    });

    audio.addEventListener('ended', function() {
        <?php if ($musicLoop): ?>
        if (<?php if ($musicShuffle): ?>false<?php else: ?>currentTrackIndex >= musicTracks.length - 1<?php endif; ?>) {
            loadTrack(<?php if ($musicShuffle): ?>getShuffledIndex()<?php else: ?>0<?php endif; ?>);
        } else {
            loadTrack(<?php if ($musicShuffle): ?>getShuffledIndex()<?php else: ?>currentTrackIndex + 1<?php endif; ?>);
        }
        <?php else: ?>
        if (currentTrackIndex < musicTracks.length - 1) {
            loadTrack(<?php if ($musicShuffle): ?>getShuffledIndex()<?php else: ?>currentTrackIndex + 1<?php endif; ?>);
        }
        <?php endif; ?>
    });

    function toggleMusic() {
        const statusEl = document.getElementById('music-status');
        if (audio.paused) {
            audio.play().catch(function(){});
            playIcon.classList.remove('fa-play');
            playIcon.classList.add('fa-pause');
            playIcon.style.marginLeft = '0';
            if (statusEl) statusEl.classList.remove('hidden');
        } else {
            audio.pause();
            playIcon.classList.remove('fa-pause');
            playIcon.classList.add('fa-play');
            playIcon.style.marginLeft = '2px';
            if (statusEl) statusEl.classList.add('hidden');
        }
    }

    function seekMusic(e) {
        const bar = document.getElementById('music-progress-bar');
        const rect = bar.getBoundingClientRect();
        const pct = (e.clientX - rect.left) / rect.width;
        if (audio.duration) audio.currentTime = pct * audio.duration;
    }

    function setVolume(v) {
        audio.volume = v / 100;
    }

    function loadTrack(index) {
        if (index >= musicTracks.length) index = 0;
        if (index < 0) index = musicTracks.length - 1;
        currentTrackIndex = index;
        const t = musicTracks[index];
        audio.src = t.file;
        titleEl.textContent = t.title;
        artistEl.textContent = ' - ' + t.artist;
        audio.play().catch(function(){});
        playIcon.classList.remove('fa-play');
        playIcon.classList.add('fa-pause');
        playIcon.style.marginLeft = '0';
        const statusEl = document.getElementById('music-status');
        if (statusEl) statusEl.classList.remove('hidden');
    }

    function nextTrack() {
        loadTrack(currentTrackIndex + 1);
    }

    function getShuffledIndex() {
        let idx;
        do { idx = Math.floor(Math.random() * musicTracks.length); }
        while (idx === currentTrackIndex && musicTracks.length > 1);
        return idx;
    }

    // Auto-play (controlled by admin settings)
    <?php if ($musicAutoplay): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const tryPlay = function() {
            audio.play().then(function() {
                playIcon.classList.remove('fa-play');
                playIcon.classList.add('fa-pause');
                playIcon.style.marginLeft = '0';
                const statusEl = document.getElementById('music-status');
                if (statusEl) statusEl.classList.remove('hidden');
            }).catch(function() {});
        };
        tryPlay();
        document.addEventListener('click', function autoPlayOnce() {
            if (audio.paused) tryPlay();
            document.removeEventListener('click', autoPlayOnce);
        }, { once: true });
    });
    <?php endif; ?>
    </script>
    <?php endif; ?>
    
    <footer class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <?php
            $footerContent = $content ?? [];
            $socialLinks = $footerContent['social_links'] ?? [];
            $footerInfo = $footerContent['footer'] ?? [];
            $footerI18n = $i18n['footer'] ?? [];
            $footerText = $footerI18n['text'] ?? $footerInfo['text'] ?? '个人空间。用心构建。';
            ?>
            <div class="flex flex-col md:flex-row justify-between items-center gap-y-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    &copy; <?= date('Y') ?> <?= sanitizeHtml($footerText) ?>
                    <?php if (!empty($footerInfo['icp'])): ?>
                        <span class="ml-2"><?= sanitizeHtml($footerInfo['icp']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($footerSocialLinks): ?>
                <div class="flex items-center gap-x-4">
                    <?php if (!empty($socialLinks['github'])): ?>
                        <a href="<?= sanitizeHtml($socialLinks['github']) ?>" target="_blank" title="GitHub" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fa-brands fa-github text-xl"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($socialLinks['bilibili'])): ?>
                        <a href="<?= sanitizeHtml($socialLinks['bilibili']) ?>" target="_blank" title="Bilibili" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fa-brands fa-bilibili text-xl"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($socialLinks['zhihu'])): ?>
                        <a href="<?= sanitizeHtml($socialLinks['zhihu']) ?>" target="_blank" title="Zhihu" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fa-brands fa-zhihu text-xl"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($socialLinks['twitter'])): ?>
                        <a href="<?= sanitizeHtml($socialLinks['twitter']) ?>" target="_blank" title="Twitter" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fa-brands fa-x-twitter text-xl"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($socialLinks['weibo'])): ?>
                        <a href="<?= sanitizeHtml($socialLinks['weibo']) ?>" target="_blank" title="Weibo" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fa-brands fa-weibo text-xl"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    
    <!-- Theme Toggle + Back to Top -->
    <?php if ($darkModeToggle): ?>
    <button id="theme-toggle" title="<?= sanitizeHtml($i18n['footer']['tooltip_theme'] ?? '切换主题') ?>"
            class="fixed bottom-6 right-6 z-50 w-12 h-12 flex items-center justify-center bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 rounded-2xl text-gray-700 dark:text-gray-200 hover:scale-110 active:scale-95 transition-all">
        <i class="fa-solid fa-moon text-xl dark:hidden"></i>
        <i class="fa-solid fa-sun text-xl hidden dark:block text-yellow-400"></i>
    </button>
    
    <button id="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="<?= sanitizeHtml($i18n['footer']['tooltip_back_to_top'] ?? '回到顶部') ?>"
            class="fixed bottom-20 right-6 z-50 w-10 h-10 hidden items-center justify-center bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 rounded-xl text-gray-500 hover:scale-110 transition-all">
        <i class="fa-solid fa-arrow-up"></i>
    </button>
    <?php endif; ?>
    
    <script>
    // Back to top visibility
    window.addEventListener('scroll', function() {
        const btn = document.getElementById('back-to-top');
        if (btn) btn.style.display = window.scrollY > 300 ? 'flex' : 'none';
    });
    

    </script>
    <script src="assets/js/enhancements.js"></script>
</body>
</html>