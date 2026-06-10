// Debug: test API responses
(async function() {
    try {
        var hotRes = await fetch('/api/songs/hot').then(r => r.json());
        var hotData = hotRes.data;
        var out = [];
        out.push('hotRes.code: ' + hotRes.code);
        out.push('hotRes.data type: ' + typeof hotRes.data);
        out.push('hotRes.data is array: ' + Array.isArray(hotRes.data));
        out.push('hotRes.data length: ' + (hotRes.data ? hotRes.data.length : 'null'));

        // Simulate what loadDiscover does
        var songs = hotData || [];
        out.push('songs to render: ' + songs.length);

        var latestRes = await fetch('/api/songs/latest').then(r => r.json());
        out.push('latestRes.data length: ' + (latestRes.data ? latestRes.data.length : 'null'));

        out.push('token: ' + (localStorage.getItem('token') ? 'exists' : 'missing'));

        document.title = out.join(' | ');
        var div = document.createElement('div');
        div.style.cssText = 'position:fixed;top:0;left:0;right:0;background:rgba(0,0,0,0.8);color:#fff;padding:10px;z-index:9999;font-size:12px;white-space:pre-wrap;';
        document.body.appendChild(div);
        div.textContent = out.join('\n');
    } catch(e) {
        document.title = 'ERROR: ' + e.message;
    }
})();
