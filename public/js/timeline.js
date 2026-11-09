// Timeline termin. Tanpa build (npm belum bisa dipakai), jadi createElement, bukan JSX.
// Bila React/fetch gagal, isi #timeline dari Blade (fallback) dibiarkan.
(function () {
    var el = document.getElementById('timeline');
    if (!el || !window.React || !window.ReactDOM) return;
    var h = React.createElement;
    var color = { menunggu: 'secondary', bukti_lengkap: 'warning', selesai: 'success' };

    function Item(props) {
        var t = props.t;
        return h('li', { className: 'border-top py-2' },
            h('div', { className: 'd-flex justify-content-between' },
                h('div', null,
                    h('a', { href: t.url, className: 'fw-semibold' }, t.number + '. ' + t.title),
                    h('br'),
                    h('small', { className: 'text-muted' }, 'Jatuh tempo ' + t.due_label)),
                h('div', { className: 'text-end' },
                    h('div', { className: 'fw-semibold' }, t.amount_label),
                    h('span', { className: 'badge text-bg-' + (color[t.status] || 'secondary') }, t.status_label))),
            t.photos.length ? h('div', { className: 'd-flex gap-1 mt-2 flex-wrap' }, t.photos.map(function (p) {
                return h('a', { key: p.url, href: p.url },
                    h('img', { src: p.url, alt: 'Foto ' + p.kind, title: p.kind + ', ' + p.at, width: 64, height: 64, loading: 'lazy', className: 'rounded', style: { objectFit: 'cover' } }));
            })) : null);
    }

    fetch(el.dataset.src, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (d) {
            ReactDOM.createRoot(el).render(h('ol', { className: 'list-unstyled mb-0' }, d.termins.map(function (t) { return h(Item, { key: t.id, t: t }); })));
        })
        .catch(function () { /* fallback Blade tetap tampil */ });
})();
