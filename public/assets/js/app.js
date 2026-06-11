/**
 * MusicSite - Frontend App
 */
(function () {
    var API_BASE = "/api";
    var currentSong = null;
    var isPlaying = false;
    var playlist = [];
    var currentIndex = -1;
    var playMode = "sequence";
    var audioEl = null;

    async function api(p, o) {
        o = o || {};
        var token = localStorage.getItem("token") || "";
        var headers = { "Content-Type": "application/json" };
        if (token) headers["Authorization"] = "Bearer " + token;
        var res = await fetch(API_BASE + p, {
            headers: headers,
            method: o.method || "GET",
            body: o.body || null
        });
        return res.json();
    }

    function showToast(msg) {
        var t = document.getElementById("toast");
        if (!t) return;
        t.textContent = msg;
        t.classList.add("show");
        setTimeout(function() { t.classList.remove("show"); }, 2000);
    }

    function initAudio() {
        if (!audioEl) {
            audioEl = new Audio();
            audioEl.addEventListener("timeupdate", updateProgress);
            audioEl.addEventListener("ended", playNext);
            audioEl.addEventListener("play", function() {
                isPlaying = true; updatePlayBtn();
                var v = document.getElementById("vinylDisc");
                if (v) v.classList.add("playing");
            });
            audioEl.addEventListener("pause", function() {
                isPlaying = false; updatePlayBtn();
                var v = document.getElementById("vinylDisc");
                if (v) v.classList.remove("playing");
            });
        }
    }

    function playSong(song) {
        if (!song) return;
        initAudio();
        currentSong = song;
        document.getElementById("miniTitle").textContent = song.title;
        document.getElementById("miniArtist").textContent = song.artist || "-";
        document.getElementById("miniCover").src = song.cover_path || "/assets/images/default-cover.svg";
        document.getElementById("fullTitle").textContent = song.title;
        document.getElementById("fullArtist").textContent = song.artist || "-";
        var vc = document.getElementById("vinylCover");
        if (vc) vc.style.backgroundImage = "url(" + (song.cover_path || "/assets/images/default-cover.svg") + ")";
        if (song.file_path) { audioEl.src = song.file_path; audioEl.play().catch(function(){}); }
        renderLyrics(song.lyrics);
        if (currentIndex === -1 || playlist[currentIndex].id !== song.id) {
            var ex = playlist.findIndex(function(s){return s.id === song.id;});
            if (ex !== -1) currentIndex = ex; else { playlist.push(song); currentIndex = playlist.length - 1; }
        }
        api("/songs/" + song.id + "/play");
        document.getElementById("miniPlayer").style.display = "block";
    }

    function togglePlay() { if (!audioEl) return; if (isPlaying) audioEl.pause(); else audioEl.play().catch(function(){}); }

    function playNext() {
        if (!playlist.length) return;
        var n;
        if (playMode === "shuffle") n = Math.floor(Math.random() * playlist.length);
        else if (playMode === "repeat") n = currentIndex;
        else n = (currentIndex + 1) % playlist.length;
        currentIndex = n; playSong(playlist[n]);
    }

    function playPrev() {
        if (!playlist.length) return;
        currentIndex = (currentIndex - 1 + playlist.length) % playlist.length;
        playSong(playlist[currentIndex]);
    }

    function updatePlayBtn() {
        var mp = document.getElementById("miniPlayBtn");
        var fp = document.getElementById("fullPlayBtn");
        if (mp) { mp.querySelector(".play-icon").classList.toggle("hidden", isPlaying); mp.querySelector(".pause-icon").classList.toggle("hidden", !isPlaying); }
        if (fp) { fp.querySelector(".play-icon").classList.toggle("hidden", isPlaying); fp.querySelector(".pause-icon").classList.toggle("hidden", !isPlaying); }
    }

    function updateProgress() {
        if (!audioEl) return;
        var pct = audioEl.duration ? (audioEl.currentTime / audioEl.duration * 100) : 0;
        document.getElementById("progressFill").style.width = pct + "%";
        document.getElementById("progressThumb").style.left = pct + "%";
        document.getElementById("miniProgress").style.width = pct + "%";
        document.getElementById("timeCurrent").textContent = fmt(audioEl.currentTime);
        document.getElementById("timeTotal").textContent = fmt(audioEl.duration || 0);
        highlightLyric(audioEl.currentTime);
    }

    function highlightLyric(currentTime) {
        var scroll = document.getElementById("lyricsScroll");
        if (!scroll) return;
        var lines = scroll.querySelectorAll(".lyric-line");
        if (!lines.length) return;
        var activeIdx = 0;
        for (var i = 0; i < lines.length; i++) {
            var t = parseFloat(lines[i].dataset.time);
            if (!isNaN(t) && t <= currentTime) {
                activeIdx = i;
            }
        }
        lines.forEach(function(l){ l.classList.remove("active"); });
        var activeEl = lines[activeIdx];
        if (activeEl) {
            activeEl.classList.add("active");
            activeEl.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    }

    function fmt(s) { if (!s || isNaN(s)) return "0:00"; var m = Math.floor(s / 60); var sec = Math.floor(s % 60); return m + ":" + (sec < 10 ? "0" : "") + sec; }

    function renderLyrics(lrc) {
        var c = document.getElementById("lyricsScroll"); if (!c) return; c.innerHTML = "";
        if (!lrc) { c.innerHTML = "<p class=\"lyric-placeholder\">暂无歌词</p>"; return; }
        var lines = lrc.split("\n").map(function(l){
            var m = l.match(/\[(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?\](.*)/);
            if (!m || !m[4].trim()) return null;
            return { time: parseInt(m[1])*60+parseInt(m[2])+(parseInt(m[3]||0)/1000), text: m[4].trim() };
        }).filter(Boolean);
        if (!lines.length) { c.innerHTML = "<p class=\"lyric-placeholder\">暂无歌词</p>"; return; }
        lines.forEach(function(l){
            var p = document.createElement("p"); p.className = "lyric-line"; p.textContent = l.text; p.dataset.time = l.time; c.appendChild(p);
        });
    }

    function cyclePlayMode() {
        var modes = ["sequence","shuffle","repeat"];
        var idx = modes.indexOf(playMode); playMode = modes[(idx+1)%modes.length];
        var btn = document.getElementById("modeBtn"); if (!btn) return;
        btn.querySelectorAll(".icon").forEach(function(el){el.classList.add("hidden")});
        var icon = btn.querySelector(".mode-"+playMode); if (icon) icon.classList.remove("hidden");
        var lb = {sequence:"顺序播放",shuffle:"随机播放",repeat:"单曲循环"};
        showToast(lb[playMode]);
    }

    function renderSongItem(song) {
        var div = document.createElement("div"); div.className = "song-item"; div.dataset.id = song.id;
        div.innerHTML = "<img class=\"song-cover\" src=\""+(song.cover_path||"/assets/images/default-cover.svg")+"\" alt=\"\">" +
            "<div class=\"song-info\"><p class=\"song-title\">"+esc(song.title)+"</p><p class=\"song-artist\">"+esc(song.artist||"-")+"</p></div>" +
            "<div class=\"song-meta\"><span class=\"song-duration\">"+fmt(song.duration)+"</span>" +
            "<button class=\"song-fav-btn"+(song.is_favorite?" active":"")+"\" data-fav-id=\""+song.id+"\">" +
            "<svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\""+(song.is_favorite?"currentColor":"none")+"\" stroke=\"currentColor\" stroke-width=\"2\" style=\"pointer-events:none\"><path d=\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\"/></svg></button></div>";
        div.addEventListener("click", function(e) {
            if (e.target.closest(".song-fav-btn")) { e.stopPropagation(); toggleFavorite(parseInt(e.target.closest(".song-fav-btn").dataset.favId)); return; }
            playSong(Object.assign({},song)); openFullPlayer();
        });
        return div;
    }

    function esc(s) { var d = document.createElement("div"); d.textContent = s; return d.innerHTML; }

    async function toggleFavorite(songId) {
        var token = localStorage.getItem("token");
        if (!token) { showToast("请先登录"); return; }
        var res = await api("/favorite/" + songId, { method: "POST" });
        if (res.code === 0) {
            showToast(res.message);
            if (currentSong && currentSong.id === songId) currentSong.is_favorite = res.data.is_favorite ? 1 : 0;
            updateFavBtn();
            if (window._refreshFavorites) window._refreshFavorites();
        }
    }

    function updateFavBtn() {
        var btn = document.getElementById("favBtn"); if (!btn || !currentSong) return;
        btn.style.color = currentSong.is_favorite ? "var(--accent)" : "";
        btn.querySelector("svg").setAttribute("fill", currentSong.is_favorite ? "var(--accent)" : "none");
    }

    function switchTab(tab) {
        document.querySelectorAll(".tab").forEach(function(t){t.classList.toggle("active",t.dataset.tab===tab)});
        document.querySelectorAll(".page").forEach(function(p){p.classList.toggle("active",p.id==="page-"+tab)});
    }

    function openFullPlayer() { document.getElementById("fullPlayer").classList.add("active"); }
    function closeFullPlayer() { document.getElementById("fullPlayer").classList.remove("active"); }
    function showSubPage(id) { document.getElementById("page-mine").classList.remove("active"); document.getElementById(id).classList.add("active"); }
    function hideSubPage() { document.querySelectorAll(".sub-page").forEach(function(p){p.classList.remove("active")}); document.getElementById("page-mine").classList.add("active"); }

    async function loadUserProfile() {
        var token = localStorage.getItem("token");
        var lp = document.getElementById("loginPrompt");
        var profile = document.getElementById("userProfile");
        var lb = document.getElementById("logoutBtn");
        if (!token) {
            if (lp) lp.style.display = "";
            if (profile) profile.classList.add("hidden");
            if (lb) lb.textContent = "登录";
            return;
        }
        var res = await api("/auth/me");
        if (res.code === 0) {
            var user = res.data;
            if (lp) lp.style.display = "none";
            if (profile) profile.classList.remove("hidden");
            var ai = document.getElementById("userAvatarImg"); if (ai) ai.src = user.avatar;
            var nk = document.getElementById("userNickname"); if (nk) nk.textContent = user.nickname || user.username;
            var rl = document.getElementById("userRole"); if (rl) rl.textContent = user.role === "admin" ? "管理员" : "用户";
            if (user.role === "admin") { var ab = document.getElementById("adminMenuBtn"); if (ab) ab.style.display = ""; }
            if (lb) lb.textContent = "退出登录";
        } else {
            localStorage.removeItem("token");
            if (lp) lp.style.display = "";
            if (profile) profile.classList.add("hidden");
            if (lb) lb.textContent = "登录";
        }
    }

    async function handleLogout() {
        await api("/auth/logout", { method: "POST" });
        localStorage.removeItem("token");
        window.location.replace("/login.html");
    }

    async function loadDiscover() {
        var catRes = await api("/categories");
        if (catRes.code === 0) {
            var container = document.getElementById("categoryList");
            catRes.data.forEach(function(cat){
                var tag = document.createElement("span"); tag.className = "category-tag"; tag.textContent = cat.name; tag.dataset.id = cat.id;
                tag.addEventListener("click", async function(){
                    container.querySelectorAll(".category-tag").forEach(function(t){t.classList.remove("active")});
                    tag.classList.add("active");
                    var sr = await api("/songs?category_id=" + cat.id);
                    renderSongList(document.getElementById("hotList"), sr.data?.list || []);
                });
                container.appendChild(tag);
            });
        }
        var hotRes = await api("/songs/hot");
        if (hotRes.code === 0) {
            var hotSongs = Array.isArray(hotRes.data) ? hotRes.data : (hotRes.data?.list || []);
            hotSongs.forEach(function(s){ s.is_favorite = 0; });
            renderSongList(document.getElementById("hotList"), hotSongs);
        }
        var latestRes = await api("/songs/latest");
        if (latestRes.code === 0) {
            var latestSongs = Array.isArray(latestRes.data) ? latestRes.data : (latestRes.data?.list || []);
            latestSongs.forEach(function(s){ s.is_favorite = 0; });
            renderSongList(document.getElementById("latestList"), latestSongs);
        }
    }

    function renderSongList(container, songs) {
        if (!container) return; container.innerHTML = "";
        if (!songs.length) { container.innerHTML = "<div class=\"empty-state\"><p>暂无歌曲</p></div>"; return; }
        songs.forEach(function(s){container.appendChild(renderSongItem(s))});
    }

    var searchTimer = null;
    function handleSearch(e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async function(){
            var kw = e.target.value.trim(); if (!kw) return;
            var res = await api("/songs?keyword=" + encodeURIComponent(kw));
            if (res.code === 0) { renderSongList(document.getElementById("hotList"), res.data?.list || []); renderSongList(document.getElementById("latestList"), []); }
        }, 500);
    }

    async function loadFavorites() { var res = await api("/favorites"); if (res.code === 0) renderSongList(document.getElementById("favoritesList"), res.data?.list || []); }
    async function loadHistory() { var res = await api("/history"); if (res.code === 0) renderSongList(document.getElementById("historyList"), res.data?.list || []); }

    async function init() {
        var token = localStorage.getItem("token");
        if (!token) { window.location.replace("/login.html"); return; }
        try {
            var meRes = await api("/auth/me");
            if (meRes.code !== 0) { localStorage.removeItem("token"); window.location.replace("/login.html"); return; }
        } catch(e) { console.error('Auth check failed:', e); }

        document.querySelectorAll(".tab").forEach(function(tab){tab.addEventListener("click",function(){switchTab(tab.dataset.tab)})});
        var avatarEl = document.getElementById("userAvatar");
        var avatarClick = function(){ switchTab("mine"); };
        avatarEl.addEventListener("click", avatarClick);
        var avatarSvg = avatarEl.querySelector("svg");
        if (avatarSvg) avatarSvg.addEventListener("click", avatarClick);

        loadUserProfile();
        try { await loadDiscover(); } catch(e) { console.error(e); }

        document.getElementById("miniPlayBtn").addEventListener("click", togglePlay);
        document.getElementById("miniNextBtn").addEventListener("click", playNext);
    function downloadSong() {
        if (!currentSong || !currentSong.file_path) return;
        var url = currentSong.file_path;
        // 如果路径是相对路径，加上当前 origin
        if (url.startsWith('/')) url = window.location.origin + url;
        var filename = (currentSong.title || 'song') + '.mp3';
        fetch(url).then(function(r){ return r.blob(); }).then(function(blob){
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        }).catch(function(){
            // fallback: 直接打开链接
            window.open(url, '_blank');
        });
    }
        document.getElementById("miniContent").addEventListener("click", openFullPlayer);
        document.getElementById("fullBack").addEventListener("click", closeFullPlayer);
        document.getElementById("fullPlayBtn").addEventListener("click", togglePlay);
        document.getElementById("prevBtn").addEventListener("click", playPrev);
        document.getElementById("nextBtn").addEventListener("click", playNext);
        document.getElementById("modeBtn").addEventListener("click", cyclePlayMode);
        document.getElementById("favBtn").addEventListener("click", function(){if(currentSong) toggleFavorite(currentSong.id)});
        document.getElementById("downloadBtn").addEventListener("click", downloadSong);
        document.getElementById("progressBar").addEventListener("click", function(e){
            if (!audioEl || !audioEl.duration) return;
            var rect = e.currentTarget.getBoundingClientRect();
            audioEl.currentTime = ((e.clientX - rect.left) / rect.width) * audioEl.duration;
        });
        document.getElementById("searchInput").addEventListener("input", handleSearch);
        document.getElementById("logoutBtn").addEventListener("click", handleLogout);

        document.querySelectorAll(".menu-item").forEach(function(item){
            item.addEventListener("click", function(e){
                // Ignore clicks on child SVGs (they bubble up)
                if (e.target.tagName === "svg" || e.target.tagName === "path" || e.target.tagName === "circle" || e.target.tagName === "line" || e.target.tagName === "polygon" || e.target.tagName === "polyline") return;
                var a = item.dataset.action;
                if (a === "favorites") { showSubPage("sub-favorites"); loadFavorites(); }
                if (a === "history") { showSubPage("sub-history"); loadHistory(); }
                if (a === "admin") { window.open("/admin.html", "_blank"); }
            });
            // Disable pointer events on child SVGs
            item.querySelectorAll("svg").forEach(function(svg){ svg.style.pointerEvents = "none"; });
        });
        document.querySelectorAll("[data-back]").forEach(function(btn){btn.addEventListener("click",hideSubPage)});

        document.getElementById("miniPlayer").style.display = "none";
    }

    window._refreshFavorites = loadFavorites;
    window._refreshHistory = loadHistory;

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init); else init();
})();