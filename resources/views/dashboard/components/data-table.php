<?php
// Props — solo lo que un datatable necesita
$tableId = $tableId ?? 'datatable';
$headers = $headers ?? [];
$perPage = $perPage ?? [10, 25, 50];
?>

<div id="<?= htmlspecialchars($tableId) ?>-wrapper"
    data-table-id="<?= htmlspecialchars($tableId) ?>">
</div>

@push('scripts')
<script>
    (function() {

        class DataTable {
            constructor(container, data, headers, options = {}) {
                this.container = document.querySelector(container);
                if (!this.container) return;
                this.data = data;
                this.headers = headers;
                if (Array.isArray(options)) {
                    options = {
                        rowsPerPage: options[0],
                        rowsPerPageOptions: options
                    };
                }
                this.page = 1;
                this.rowsPerPage = options.rowsPerPage ?? 10;
                this.rowsPerPageOptions = options.rowsPerPageOptions ?? [10, 25, 50];
                this.searchTerm = '';
                this.sortedBy = null;
                this.sortedAsc = true;
                this._listeners = [];
            }

            init() {
                if (!this.container) return this;
                this.container.innerHTML = '';
                this._wrapper = document.createElement('div');
                this._wrapper.className = 'dt-wrapper';
                this.container.appendChild(this._wrapper);
                this._renderHeader();
                this._renderTable();
                this._render();
                return this;
            }

            update(newData) {
                this.data = newData;
                this.page = 1;
                this._render();
                return this;
            }

            showLoading() {
                const tbody = this.container?.querySelector('tbody');
                if (!tbody) return;
                const cols = Object.keys(this.headers).length;
                tbody.innerHTML = Array.from({
                        length: 5
                    }, () =>
                    `<tr><td colspan="${cols}" class="px-4 py-3">
                    <div class="animate-pulse h-4 bg-gray-200 rounded w-3/4"></div>
                </td></tr>`
                ).join('');
            }

            destroy() {
                this._listeners.forEach(({
                    el,
                    type,
                    fn
                }) => el.removeEventListener(type, fn));
                this._listeners = [];
                if (this.container) this.container.innerHTML = '';
            }

            _on(el, type, fn) {
                el.addEventListener(type, fn);
                this._listeners.push({
                    el,
                    type,
                    fn
                });
            }

            _renderHeader() {
                const header = document.createElement('div');
                header.className = 'datatable-header';

                const select = document.createElement('select');
                select.className = 'datatable-header-input';
                this.rowsPerPageOptions.forEach(opt => {
                    const o = document.createElement('option');
                    o.value = opt;
                    o.textContent = `${opt} filas`;
                    if (opt === this.rowsPerPage) o.selected = true;
                    select.appendChild(o);
                });
                this._on(select, 'change', e => {
                    this.rowsPerPage = parseInt(e.target.value);
                    this.page = 1;
                    this._render();
                });

                const search = document.createElement('input');
                search.type = 'text';
                search.placeholder = 'Buscar...';
                search.className = 'datatable-header-input';
                this._on(search, 'input', e => {
                    this.searchTerm = e.target.value;
                    this.page = 1;
                    this._render();
                });

                header.appendChild(select);
                header.appendChild(search);
                this._wrapper.appendChild(header);
            }

            _renderTable() {
                const responsive = document.createElement('div');
                responsive.className = 'table-container-responsive';

                const table = document.createElement('table');
                table.className = 'datatable-table';

                const thead = document.createElement('thead');
                const tr = document.createElement('tr');
                tr.className = 'datatable-table-thead-tr';

                for (const key in this.headers) {
                    const th = document.createElement('th');
                    th.className = 'datatable-table-thead-th';
                    th.dataset.key = key;
                    th.textContent = this.headers[key];
                    if (key !== 'action' && key !== 'actions') {
                        th.style.cursor = 'pointer';
                        this._on(th, 'click', () => {
                            if (this.sortedBy === key) {
                                this.sortedAsc = !this.sortedAsc;
                            } else {
                                this.sortedBy = key;
                                this.sortedAsc = true;
                            }
                            table.querySelectorAll('th').forEach(h => {
                                h.textContent = this.headers[h.dataset.key];
                            });
                            th.textContent += this.sortedAsc ? ' ↑' : ' ↓';
                            this._render();
                        });
                    }
                    tr.appendChild(th);
                }

                thead.appendChild(tr);
                table.appendChild(thead);
                const tbody = document.createElement('tbody');
                tbody.className = 'datatable-table-tbody';
                table.appendChild(tbody);
                responsive.appendChild(table);
                this._wrapper.appendChild(responsive);
                this._table = table;
            }

            _render() {
                if (!this._table) return;
                const filtered = this._filtrar();
                const sorted = this._ordenar(filtered);
                const total = Math.ceil(sorted.length / this.rowsPerPage) || 1;
                if (this.page > total) this.page = total;
                const start = (this.page - 1) * this.rowsPerPage;
                const visible = sorted.slice(start, start + this.rowsPerPage);
                const tbody = this._table.querySelector('tbody');
                tbody.innerHTML = '';

                if (visible.length === 0) {
                    const cols = Object.keys(this.headers).length;
                    tbody.innerHTML = `<tr><td colspan="${cols}"
                    class="datatable-table-tbody-td text-center py-10 text-gray-400">
                    ${this.searchTerm ? `Sin resultados para "${this.searchTerm}"` : 'No hay datos disponibles'}
                </td></tr>`;
                } else {
                    visible.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.className = 'datatable-table-tbody-tr';
                        for (const key in this.headers) {
                            const td = document.createElement('td');
                            td.className = 'datatable-table-tbody-td';
                            td.innerHTML = row[key] ?? '';
                            tr.appendChild(td);
                        }
                        tbody.appendChild(tr);
                    });
                }

                const old = this._wrapper.querySelector('.datatable-pagination');
                if (old) old.remove();
                this._wrapper.appendChild(this._renderPagination(sorted.length, total, start, visible.length));
            }

            _renderPagination(totalItems, totalPages, start, visibleCount) {
                const nav = document.createElement('div');
                nav.className = 'datatable-pagination';

                const info = document.createElement('span');
                info.className = 'table-btn-points';
                info.textContent = totalItems > 0 ?
                    `Mostrando ${start + 1}–${start + visibleCount} de ${totalItems}` :
                    '';
                nav.appendChild(info);

                const prev = document.createElement('button');
                prev.className = 'table-btn-previous';
                prev.disabled = this.page === 1;
                prev.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
                this._on(prev, 'click', () => {
                    this.page--;
                    this._render();
                });
                nav.appendChild(prev);

                this._pageButtons(totalPages).forEach(b => nav.appendChild(b));

                const next = document.createElement('button');
                next.className = 'table-btn-next';
                next.disabled = this.page === totalPages;
                next.innerHTML = '<svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M12.828 10l-4.828-4.828 1.414-1.414L16.656 10l-7.757 7.757-1.414-1.414L12.828 10z"/></svg>';
                this._on(next, 'click', () => {
                    this.page++;
                    this._render();
                });
                nav.appendChild(next);

                return nav;
            }

            _pageButtons(totalPages) {
                const btns = [];
                let s, e;
                if (totalPages <= 5) {
                    s = 1;
                    e = totalPages;
                } else if (this.page <= 3) {
                    s = 1;
                    e = 5;
                } else if (this.page + 1 >= totalPages) {
                    s = totalPages - 4;
                    e = totalPages;
                } else {
                    s = this.page - 2;
                    e = this.page + 2;
                }

                if (s > 1) {
                    btns.push(this._pageBtn(1));
                    if (s > 2) btns.push(this._dots());
                }
                for (let i = s; i <= e; i++) btns.push(this._pageBtn(i));
                if (e < totalPages) {
                    if (e < totalPages - 1) btns.push(this._dots());
                    btns.push(this._pageBtn(totalPages));
                }
                return btns;
            }

            _pageBtn(n) {
                const btn = document.createElement('button');
                btn.className = 'table-btn-number' + (n === this.page ? ' active' : '');
                btn.textContent = n;
                btn.disabled = n === this.page;
                this._on(btn, 'click', () => {
                    this.page = n;
                    this._render();
                });
                return btn;
            }

            _dots() {
                const s = document.createElement('span');
                s.className = 'table-btn-points';
                s.textContent = '...';
                return s;
            }

            _filtrar() {
                if (!this.searchTerm) return this.data;
                const term = this.searchTerm.toLowerCase();
                return this.data.filter(row =>
                    Object.values(row).some(v =>
                        v != null && v.toString().toLowerCase().includes(term)
                    )
                );
            }

            _ordenar(data) {
                if (!this.sortedBy) return data;
                return [...data].sort((a, b) => {
                    const va = a[this.sortedBy],
                        vb = b[this.sortedBy];
                    if (va == null) return 1;
                    if (vb == null) return -1;
                    return typeof va === 'string' ?
                        (this.sortedAsc ? va.localeCompare(vb) : vb.localeCompare(va)) :
                        (this.sortedAsc ? va - vb : vb - va);
                });
            }
        }

        // Exponer globalmente
        window.DataTable = DataTable;

        // Arranque automático si la página define __dt_init_{tableId}
        const wrapper = document.querySelector('[data-table-id]');
        if (!wrapper) return;

        const tableId = wrapper.dataset.tableId;
        if (typeof window.__datatables === 'undefined') window.__datatables = {};
        window.__datatables[tableId] = null;

        document.addEventListener('DOMContentLoaded', () => {
            const initFn = window['__dt_init_' + tableId];
            if (typeof initFn === 'function') initFn();
        });

    })();
</script>
@endpush