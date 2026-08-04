import './bootstrap';
import 'bootstrap';
import { Modal, Offcanvas } from 'bootstrap';

document.addEventListener('shown.bs.offcanvas', (event) => {
    event.target.querySelector('a, button')?.focus();
});

const viewer = document.querySelector('#tree-viewer');
const dataElement = document.querySelector('#tree-data');

if (viewer && dataElement) {
    const tree = JSON.parse(dataElement.textContent);
    const stage = viewer.querySelector('[data-tree-stage]');
    const nodesLayer = viewer.querySelector('[data-tree-nodes]');
    const edgesLayer = viewer.querySelector('[data-tree-edges]');
    const drawerElement = document.querySelector('#tree-member-drawer');
    const detail = drawerElement.querySelector('[data-tree-detail]');
    const relativeModalElement = document.querySelector('#tree-relative-modal');
    const relativeForm = relativeModalElement?.querySelector('[data-tree-relative-form]');
    const nodes = new Map(tree.nodes.map((node) => [node.uuid, node]));
    let scale = 1;
    let offsetX = 0;
    let offsetY = 0;
    let dragging = false;
    let start = null;

    const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
    const applyTransform = () => { stage.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale})`; };
    const center = (node = tree.nodes.find((item) => item.is_root)) => {
        scale = 1;
        offsetX = viewer.clientWidth / 2 - node.position.x;
        offsetY = viewer.clientHeight / 2 - node.position.y;
        applyTransform();
    };
    const photo = (node) => node.profile_photo_url
        ? `<img class="tree-node-photo" src="${escape(node.profile_photo_url)}" alt="Foto ${escape(node.name)}">`
        : `<span class="tree-node-photo tree-node-photo-placeholder" aria-hidden="true">${escape(node.name.charAt(0))}</span>`;
    const memorialPrefix = (node) => {
        return node.memorial_prefix ?? (node.is_alive ? '' : 'Mendiang ');
    };
    const openRelativeForm = (node, relation) => {
        if (!relativeModalElement || !relativeForm) return;
        const labels = { parent: 'orang tua', spouse: 'pasangan', child: 'anak' };
        relativeForm.action = relativeModalElement.dataset.relativeUrlTemplate.replace('__member__', encodeURIComponent(node.uuid));
        relativeForm.querySelector('[data-tree-relative-relation]').value = relation;
        relativeModalElement.querySelector('#tree-relative-modal-title').textContent = `Tambah ${labels[relation]} untuk ${node.name}`;
        relativeModalElement.querySelector('[data-tree-relative-description]').textContent = `Anggota baru akan langsung dihubungkan sebagai ${labels[relation]} dari ${node.name}.`;
        Offcanvas.getInstance(drawerElement)?.hide();
        Modal.getOrCreateInstance(relativeModalElement).show();
    };

    edgesLayer.setAttribute('width', tree.viewport.width);
    edgesLayer.setAttribute('height', tree.viewport.height);
    tree.edges.forEach((edge) => {
        const source = nodes.get(edge.source_uuid);
        const target = nodes.get(edge.target_uuid);
        if (!source || !target) return;
        edgesLayer.insertAdjacentHTML('beforeend', `<line class="tree-edge" data-edge="${source.uuid}:${target.uuid}" x1="${source.position.x}" y1="${source.position.y}" x2="${target.position.x}" y2="${target.position.y}"></line><text class="tree-edge-label" x="${(source.position.x + target.position.x) / 2}" y="${(source.position.y + target.position.y) / 2 - 6}">${escape(edge.relationship)}</text>`);
    });
    tree.nodes.forEach((node) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `tree-node p-2 ${node.is_root ? 'tree-node-root' : ''} ${node.is_alive ? '' : 'tree-node-deceased'}`;
        button.dataset.treeNode = node.uuid;
        button.style.left = `${node.position.x}px`;
        button.style.top = `${node.position.y}px`;
        button.setAttribute('aria-label', `${node.name}, ${node.relationship_label ?? 'anggota keluarga'}`);
        button.innerHTML = `<span class="d-flex gap-2 align-items-center">${photo(node)}<span class="min-width-0"><strong class="d-block text-truncate">${memorialPrefix(node)}${escape(node.name)}</strong>${node.nickname ? `<span class="tree-nickname d-block text-body-secondary text-truncate">(${escape(node.nickname)})</span>` : ''}<small>${escape(node.birth_year ?? '?')}${node.is_alive ? '–sekarang' : `–${escape(node.death_year ?? '?')}`}</small>${node.relationship_label ? `<span class="tree-relationship d-block text-primary">${escape(node.relationship_label)}</span>` : ''}</span></span>`;
        button.addEventListener('click', () => {
            const relativeActions = node.can_add_relative ? `<div class="mt-3"><p class="small text-body-secondary mb-2">Tambah dari anggota ini</p><div class="d-flex flex-wrap gap-2"><button class="btn btn-outline-primary btn-sm" type="button" data-relative-action="parent">Tambah orang tua</button><button class="btn btn-outline-primary btn-sm" type="button" data-relative-action="spouse">Tambah pasangan</button><button class="btn btn-outline-primary btn-sm" type="button" data-relative-action="child">Tambah anak</button></div></div>` : '';
            detail.innerHTML = `<div class="text-center mb-3">${photo(node)}<h3 class="h5 mt-2">${memorialPrefix(node)}${escape(node.name)}</h3><p class="text-primary">${escape(node.relationship_label ?? 'Hubungan belum dikenali')}</p></div><dl><dt>Pekerjaan</dt><dd>${escape(node.occupation || 'Belum diisi')}</dd><dt>Pendidikan</dt><dd>${escape(node.education || 'Belum diisi')}</dd><dt>Biografi</dt><dd>${escape(node.biography || 'Belum ada biografi.')}</dd></dl><a class="btn btn-primary" href="/members/${encodeURIComponent(node.uuid)}">Lihat profil lengkap</a>${relativeActions}`;
            detail.querySelectorAll('[data-relative-action]').forEach((action) => action.addEventListener('click', () => openRelativeForm(node, action.dataset.relativeAction)));
            Offcanvas.getOrCreateInstance(drawerElement).show();
        });
        nodesLayer.append(button);
    });
    if (!document.querySelector('#show_photos').checked) document.querySelectorAll('.tree-node-photo').forEach((element) => element.hidden = true);
    if (!document.querySelector('#show_nicknames').checked) document.querySelectorAll('.tree-nickname').forEach((element) => element.hidden = true);
    if (!document.querySelector('#show_relationships').checked) document.querySelectorAll('.tree-relationship, .tree-edge-label').forEach((element) => element.hidden = true);
    if (document.querySelector('#living_only').checked) document.querySelectorAll('.tree-node-deceased').forEach((element) => element.hidden = true);

    document.querySelector('[data-tree-action="zoom-in"]').addEventListener('click', () => { scale = Math.min(2, scale + .2); applyTransform(); });
    document.querySelector('[data-tree-action="zoom-out"]').addEventListener('click', () => { scale = Math.max(.35, scale - .2); applyTransform(); });
    document.querySelector('[data-tree-action="center"]').addEventListener('click', () => center());
    document.querySelector('[data-tree-action="collapse"]').addEventListener('click', () => { document.querySelectorAll('.tree-node:not(.tree-node-root), .tree-edge, .tree-edge-label').forEach((element) => element.hidden = true); center(); });
    document.querySelector('[data-tree-action="expand"]').addEventListener('click', () => document.querySelectorAll('.tree-node, .tree-edge, .tree-edge-label').forEach((element) => element.hidden = false));
    document.querySelector('#tree-search').addEventListener('input', (event) => {
        const term = event.target.value.trim().toLocaleLowerCase('id');
        const match = tree.nodes.find((node) => node.name.toLocaleLowerCase('id').includes(term) || (node.nickname ?? '').toLocaleLowerCase('id').includes(term));
        if (term && match) { center(match); document.querySelector(`[data-tree-node="${match.uuid}"]`).focus(); }
    });
    const rootSelect = document.querySelector('#tree-root');
    if (rootSelect) {
        let rootSearchTerm = '';
        let rootSearchTimer = null;
        const applyRootFilter = () => {
            const term = rootSearchTerm.trim().toLocaleLowerCase('id');
            Array.from(rootSelect.options).forEach((option) => {
                const label = option.textContent.toLocaleLowerCase('id');
                option.hidden = Boolean(term) && !label.includes(term);
            });
            const visibleOptions = Array.from(rootSelect.options).filter((option) => !option.hidden);
            if (visibleOptions.length && rootSelect.selectedOptions[0]?.hidden) {
                rootSelect.value = visibleOptions[0].value;
            }
        };
        rootSelect.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace') {
                event.preventDefault();
                rootSearchTerm = rootSearchTerm.slice(0, -1);
            } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                rootSearchTerm += event.key;
            } else if (event.key === 'Escape') {
                rootSearchTerm = '';
            } else {
                return;
            }
            applyRootFilter();
            clearTimeout(rootSearchTimer);
            rootSearchTimer = setTimeout(() => {
                rootSearchTerm = '';
                Array.from(rootSelect.options).forEach((option) => (option.hidden = false));
            }, 1200);
        });
        rootSelect.addEventListener('focus', () => {
            rootSearchTerm = '';
            Array.from(rootSelect.options).forEach((option) => (option.hidden = false));
        });
    }
    viewer.addEventListener('pointerdown', (event) => { if (event.target.closest('.tree-node')) return; dragging = true; start = { x: event.clientX - offsetX, y: event.clientY - offsetY }; viewer.setPointerCapture(event.pointerId); });
    viewer.addEventListener('pointermove', (event) => { if (!dragging) return; offsetX = event.clientX - start.x; offsetY = event.clientY - start.y; applyTransform(); });
    viewer.addEventListener('pointerup', () => { dragging = false; });
    viewer.addEventListener('keydown', (event) => {
        const movements = { ArrowLeft: [30, 0], ArrowRight: [-30, 0], ArrowUp: [0, 30], ArrowDown: [0, -30] };
        if (movements[event.key]) { event.preventDefault(); offsetX += movements[event.key][0]; offsetY += movements[event.key][1]; applyTransform(); }
        if (event.key === '+' || event.key === '=') { scale = Math.min(2, scale + .2); applyTransform(); }
        if (event.key === '-') { scale = Math.max(.35, scale - .2); applyTransform(); }
    });
    center();
    viewer.classList.add('is-ready');
}
