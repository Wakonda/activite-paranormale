/**
 * SimpleDataTable
 */
class SimpleDataTable {
  static init(selector, options = {}) {
    const elements = Array.from(
      typeof selector === 'string' ? document.querySelectorAll(selector) : (selector.length ? selector : [selector])
    );
    return elements.map((el, index) => {
      const resolvedOptions = typeof options === 'function' ? options(el, index) : options;
      return new SimpleDataTable(el, resolvedOptions);
    });
  }

  constructor(selector, options = {}) {
    this.table = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!this.table) {
      throw new Error('SimpleDataTable : table introuvable pour le sélecteur "' + selector + '"');
    }

    this.options = Object.assign({
      searching: true,
      info: true,
      ordering: true,
      lengthChange: true,
      pagingType: 'full_numbers',
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      processing: true,
      serverSide: true,
      order: [[0, 'asc']],
      ajax: null,
	  data: [],
      columnDefs: [],
	  responsive: false,
      drawCallback: null,
      preDrawCallback: null,
      language: {}
    }, options);

    this.state = {
      draw: 1,
      start: 0,
      length: this.options.pageLength,
      search: '',
      order: this.options.order.map(([column, dir]) => ({ column, dir })),
      recordsTotal: 0,
      recordsFiltered: 0,
      data: []
    };

    this.lang = this._defaultLanguage();
    // Le layout (wrapper, thead/tfoot, events) est construit tout de suite,
    // de façon synchrone : dt.rows({...}).draw() peut donc être appelé
    // immédiatement après le `new SimpleDataTable(...)` sans risque.
    this._parseColumns();
    this._buildLayout();
    this._bindEvents();

    // Le fichier de langue, lui, se charge en parallèle (asynchrone) et ne
    // bloque pas la disponibilité de l'instance.
    this._firstDrawDone = false;
    this._languageReady = this._loadLanguage().then(() => {
      this._applyLanguageTexts();
      if (!this._firstDrawDone) {
        this._firstDrawDone = true;
        this._draw();
      }
    });
  }

  _defaultLanguage() {
    return {
      search: 'Rechercher\u00a0:',
      lengthMenu: 'Afficher _MENU_ \u00e9l\u00e9ments',
      info: 'Affichage de _START_ \u00e0 _END_ sur _TOTAL_ \u00e9l\u00e9ments',
      infoEmpty: 'Affichage de 0 \u00e0 0 sur 0 \u00e9l\u00e9ment',
      infoFiltered: '(filtr\u00e9 de _MAX_ \u00e9l\u00e9ments au total)',
      zeroRecords: 'Aucun \u00e9l\u00e9ment \u00e0 afficher',
      processing: 'Traitement en cours...',
      paginate: { first: 'Premier', last: 'Dernier', next: 'Suivant', previous: 'Pr\u00e9c\u00e9dent' }
    };
  }

  async _loadLanguage() {
    if (this.options.language && this.options.language.url) {
      try {
        const res = await fetch(this.options.language.url);
        const json = await res.json();
        this.lang = Object.assign({}, this.lang, json);
      } catch (e) {
        console.warn('SimpleDataTable : impossible de charger le fichier de langue', e);
      }
    }
  }
  
  // Met à jour les libellés déjà affichés (construits avec la langue par
  // défaut) une fois le fichier de langue effectivement chargé.
  _applyLanguageTexts() {
    if (this.searchLabelText) this.searchLabelText.textContent = this.lang.search + ' ';
    if (this.lengthBeforeText || this.lengthAfterText) {
      const [before, after] = this.lang.lengthMenu.split('_MENU_');
      if (this.lengthBeforeText) this.lengthBeforeText.textContent = before;
      if (this.lengthAfterText) this.lengthAfterText.textContent = after || '';
    }
    if (this.processingEl) this.processingEl.innerHTML = this.lang.processing + "<div><div></div><div></div><div></div><div></div></div>";
  }

  _parseColumns() {
    const ths = this.table.querySelectorAll('thead th');
    this.columns = Array.from(ths).map((th, index) => {
      const def = (this.options.columnDefs || []).find(d =>
        Array.isArray(d.target) ? d.target.includes(index) : d.target === index
      ) || {};

      return {
        index,
        name: th.getAttribute('data-name') || th.textContent.trim(),
        data: def.data !== undefined ? def.data : (th.getAttribute('data-data') || index),
        render: def.render || null,
        orderable: (def.orderable !== undefined ? def.orderable : true),
        searchable: th.getAttribute('data-searchable') !== 'false',
        priority: th.hasAttribute('data-priority') ? parseInt(th.getAttribute('data-priority'), 10) : index,
        hidden: false,
        el: th
      };
    });
  }

  _buildLayout() {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'sdt-wrapper';
    this.table.parentNode.insertBefore(this.wrapper, this.table);

    this.topBar = document.createElement('div');
    this.topBar.className = 'sdt-top';

    if (this.options.lengthChange) {
      const label = document.createElement('label');
      label.className = 'sdt-length';
      const select = document.createElement('select');
      (this.options.lengthMenu || [10, 25, 50, 100]).forEach(val => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val;
        if (val === this.state.length) opt.selected = true;
        select.appendChild(opt);
      });
      this.lengthSelect = select;
      const [before, after] = this.lang.lengthMenu.split('_MENU_');
      this.lengthBeforeText = document.createTextNode(before);
      this.lengthAfterText = document.createTextNode(after || '');
      label.appendChild(this.lengthBeforeText);
      label.appendChild(select);
      label.appendChild(this.lengthAfterText);
      this.topBar.appendChild(label);
    }

    if (this.options.searching) {
      const label = document.createElement('label');
      label.className = 'dataTables_filter';
      const input = document.createElement('input');
      input.type = 'search';
      this.searchInput = input;
      this.searchLabelText = document.createTextNode(this.lang.search + ' ');
      label.appendChild(this.searchLabelText);
      label.appendChild(input);
      this.topBar.appendChild(label);
    }

    this.wrapper.appendChild(this.topBar);
    this.wrapper.appendChild(this.table);
	
	this.table.classList.add("dataTable");

    if (!this.table.querySelector('tbody')) {
      this.table.appendChild(document.createElement('tbody'));
    }

    // Mode sans ajax : si aucune donnée n'est fournie via l'option `data`,
    // on récupère les lignes déjà rendues côté serveur (Twig, etc.) comme
    // jeu de données initial, avant que le premier rendu ne les efface.
    if (!this.options.ajax && (!Array.isArray(this.options.data) || !this.options.data.length)) {
	  const rows = [...this.table.rows].slice(1, -1);
		
      this._domRows = Array.from(rows).map(tr => {
        const cells = Array.from(tr.children);
        return {
          __domRow: true,
          html: cells.map(td => td.innerHTML),
          text: cells.map(td => td.textContent.trim()),
          cellClass: cells.map(td => td.className || ''),
          className: tr.className || ''
        };
      });
    }

    if (this.options.responsive) {
      const headRow = this.table.querySelector('thead tr');
      this.controlTh = document.createElement('th');
      this.controlTh.className = 'sdt-control-col';
      headRow.insertBefore(this.controlTh, headRow.firstChild);
    }

    const footRow = this.table.querySelector('tfoot tr');
    if (footRow) {
      const footCells = Array.from(footRow.children);
      footCells.forEach((cell, index) => {
        cell.dataset.colIndex = index;
      });
      if (this.options.responsive) {
        const footControlTd = document.createElement(footCells[0] && footCells[0].tagName === 'TH' ? 'th' : 'td');
        footControlTd.className = 'sdt-control-col';
        footRow.insertBefore(footControlTd, footRow.firstChild);
      }
    }

    this.bottomBar = document.createElement('div');
    this.bottomBar.className = 'sdt-bottom';

    if (this.options.info) {
      this.infoEl = document.createElement('div');
      this.infoEl.className = 'sdt-info';
      this.bottomBar.appendChild(this.infoEl);
    }

    this.paginationContainerEl = document.createElement('div');
    this.paginationContainerEl.className = 'dataTables_paginate';
    this.bottomBar.appendChild(this.paginationContainerEl);
	 

    this.paginationEl = document.createElement('ul');
    this.paginationEl.className = 'pagination';
    this.paginationContainerEl.appendChild(this.paginationEl);

    this.wrapper.appendChild(this.bottomBar);

    if (this.options.ordering) {
      this.columns.forEach(col => {
        if (col.orderable) {
          col.el.classList.add('sorting');
        }
      });
      this._updateSortIndicators();
    }
  }

  _bindEvents() {
    if (this.searchInput) {
      let debounce;
      this.searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
          this.state.search = this.searchInput.value;
          this.state.start = 0;
          this._draw();
        }, 300);
      });
    }

    if (this.lengthSelect) {
      this.lengthSelect.addEventListener('change', () => {
        this.state.length = parseInt(this.lengthSelect.value, 10);
        this.state.start = 0;
        this._draw();
      });
    }

    if (this.options.responsive) {
      let resizeDebounce;
      window.addEventListener('resize', () => {
        clearTimeout(resizeDebounce);
        resizeDebounce = setTimeout(() => this._applyResponsive(), 150);
      });
    }

    if (this.options.ordering) {
      this.columns.forEach(col => {
        if (!col.orderable) return;
        col.el.addEventListener('click', () => {
          const existing = this.state.order.find(o => o.column === col.index);
          if (existing) {
            existing.dir = existing.dir === 'asc' ? 'desc' : 'asc';
          } else {
            this.state.order = [{ column: col.index, dir: 'asc' }];
          }
          this.state.start = 0;
          this._updateSortIndicators();
          this._draw();
        });
      });
    }
  }

  _updateSortIndicators() {
    this.columns.forEach(col => {
      col.el.classList.remove('sorting_asc', 'sorting_desc');
      const o = this.state.order.find(o => o.column === col.index);
      if (o) col.el.classList.add(o.dir === 'asc' ? 'sorting_asc' : 'sorting_desc');
    });
  }

  // Transforme un objet imbriqué en query string façon PHP/DataTables : order[0][column]=0
  _buildParams(obj, prefix) {
    const pairs = [];
    for (const key in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, key)) continue;
      const value = obj[key];
      const fullKey = prefix ? `${prefix}[${key}]` : key;
      if (value === null || value === undefined) continue;
      if (typeof value === 'object') {
        pairs.push(...this._buildParams(value, fullKey));
      } else {
        pairs.push(`${encodeURIComponent(fullKey)}=${encodeURIComponent(value)}`);
      }
    }
    return pairs;
  }

  async _draw() {
    if (typeof this.options.preDrawCallback === 'function') {
      this.options.preDrawCallback({ json: this.lastJson, state: this.state });
    }

    if (this.options.processing) this._toggleProcessing(true);

    // Mode "client-side" : pas d'ajax, on trie/filtre/pagine nous-mêmes le
    // tableau fourni via l'option `data`.
    if (!this.options.ajax) {
      try {
        const result = this._processClientSide();
		console.log(result)
        const json = { draw: this.state.draw, recordsTotal: result.recordsTotal, recordsFiltered: result.recordsFiltered, data: result.rows };
        this.lastJson = json;
        this.state.draw += 1;
        this.state.recordsTotal = result.recordsTotal;
        this.state.recordsFiltered = result.recordsFiltered;
        this.state.data = result.rows;

        this._renderRows();
        this._renderInfo();
        this._renderPagination();
        if (this.options.responsive) this._applyResponsive();

        if (typeof this.options.drawCallback === 'function') {
          this.options.drawCallback({ json, state: this.state });
        }
      } finally {
        if (this.options.processing) this._toggleProcessing(false);
      }
      return;
    }

    const d = {
      draw: this.state.draw,
      start: this.state.start,
      length: this.state.length,
      search: { value: this.state.search, regex: false },
      order: {},
      columns: {}
    };

    this.state.order.forEach((o, i) => {
      d.order[i] = { column: o.column, dir: o.dir };
    });

    this.columns.forEach((col, i) => {
      d.columns[i] = {
        data: col.data,
        name: col.name,
        searchable: col.searchable,
        orderable: col.orderable,
        search: { value: '', regex: false }
      };
    });

    if (typeof this.options.ajax.data === 'function') {
      this.options.ajax.data(d);
    }

    try {
      const url = this.options.ajax.url;
      const method = (this.options.ajax.type || 'GET').toUpperCase();
      let response;
      if (method === 'GET') {
        const qs = this._buildParams(d).join('&');
        response = await fetch(`${url}${url.includes('?') ? '&' : '?'}${qs}`);
      } else {
        response = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: this._buildParams(d).join('&')
        });
      }
      const json = await response.json();
      this.lastJson = json;
      this.state.draw = (json.draw || this.state.draw) + 1;
      this.state.recordsTotal = json.recordsTotal || 0;
      this.state.recordsFiltered = json.recordsFiltered != null ? json.recordsFiltered : this.state.recordsTotal;
      this.state.data = json.data || [];

      this._renderRows();
      this._renderInfo();
      this._renderPagination();
      if (this.options.responsive) this._applyResponsive();

      if (typeof this.options.drawCallback === 'function') {
        this.options.drawCallback({ json, state: this.state });
      }
    } catch (err) {
      console.error('SimpleDataTable : erreur ajax', err);
    } finally {
      if (this.options.processing) this._toggleProcessing(false);
    }
  }

  /**
   * Recherche + tri + pagination effectués en JS pur sur this.options.data,
   * pour le cas où ajax vaut null (table 100% côté client).
   */
  _processClientSide() {
    const source = (Array.isArray(this.options.data) && this.options.data.length)
      ? this.options.data
      : (this._domRows || []);
    const recordsTotal = source.length;
console.log(this._domRows)
    // Valeur "recherchable / triable" d'une cellule : le texte visible pour
    // une ligne issue du DOM, ou row[col.data] pour une ligne "objet" classique.
    const cellValue = (row, col) => (row && row.__domRow) ? (row.text[col.index] ?? '') : row[col.data];

    // Recherche globale : compare le texte brut de chaque colonne "searchable"
    const term = (this.state.search || '').trim().toLowerCase();
    let filtered = source;
    if (term) {
      const searchableCols = this.columns.filter(c => c.searchable);
      filtered = source.filter(row =>
        searchableCols.some(col => {
          const val = cellValue(row, col);
          return val != null && String(val).toLowerCase().includes(term);
        })
      );
    }

    // Tri (colonne principale de state.order, comparaison numérique si possible)
    const order = this.state.order[0];
    if (order) {
      const col = this.columns.find(c => c.index === order.column);
      if (col) {
        filtered = filtered.slice().sort((a, b) => {
          const va = cellValue(a, col), vb = cellValue(b, col);
          const na = parseFloat(va), nb = parseFloat(vb);
          const bothNumeric = va !== '' && vb !== '' && va != null && vb != null && !isNaN(na) && !isNaN(nb);
          const cmp = bothNumeric
            ? na - nb
            : String(va != null ? va : '').localeCompare(String(vb != null ? vb : ''), undefined, { sensitivity: 'base', numeric: true });
          return order.dir === 'asc' ? cmp : -cmp;
        });
      }
    }

    const recordsFiltered = filtered.length;

    // Recale la pagination si on dépasse la dernière page (ex: recherche qui réduit le total)
    const totalPages = Math.max(1, Math.ceil(recordsFiltered / this.state.length));
    if (this.state.start >= recordsFiltered && recordsFiltered > 0) {
      this.state.start = (totalPages - 1) * this.state.length;
    }

    const rows = filtered.slice(this.state.start, this.state.start + this.state.length);

    return { rows, recordsTotal, recordsFiltered };
  }

  _toggleProcessing(show) {
    if (!this.processingEl) {
      this.processingEl = document.createElement('div');
      this.processingEl.className = 'dataTables_processing card';
      this.processingEl.innerHTML = this.lang.processing + "<div><div></div><div></div><div></div><div></div></div>";
      this.processingEl.style.display = 'none';
      this.wrapper.style.position = 'relative';
      this.wrapper.appendChild(this.processingEl);
    }
    this.processingEl.style.display = show ? '' : 'none';
    this._isLoading = show;
    if (this.paginationEl) {
      this.paginationEl.querySelectorAll('button').forEach(btn => { btn.disabled = show || btn.disabled; });
    }
  }

  _renderRows() {
    const tbody = this.table.querySelector('tbody');
    tbody.innerHTML = '';
	const totalCols = this.columns.length + (this.options.responsive ? 1 : 0);

    if (!this.state.data.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = totalCols;
      td.className = 'dataTables_empty';
      td.textContent = this.lang.zeroRecords;
      tr.appendChild(td);
      tbody.appendChild(tr);
      return;
    }

    this.state.data.forEach((row, i) => {
      const tr = document.createElement('tr');
	  tr.className = i % 2 === 0 ? "odd" : "even";

      if (this.options.responsive) {
        const controlTd = document.createElement('td');
        controlTd.className = 'sdt-control-col';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sdt-control-toggle';
        btn.setAttribute('aria-label', '+');
        controlTd.appendChild(btn);
        tr.appendChild(controlTd);
      }
	  
      this.columns.forEach(col => {
        const td = document.createElement('td');
		console.log(col)
        if (row.__domRow) {
          // Ligne issue du tbody d'origine : on restitue le HTML tel quel
          // (liens, images, balises raw Twig...), sans passer par col.data/render.
          td.innerHTML = row.html[col.index] != null ? row.html[col.index] : '';
          if (row.cellClass && row.cellClass[col.index]) td.className = row.cellClass[col.index];
        } else {
           const raw = row[col.data];
		   const columnDefs = this.options.columnDefs.find(item => item.target === col.index);
          td.innerHTML = typeof col.render === 'function' ? col.render(raw, row) : (raw != null ? raw : '');
        }
		

		if(typeof columnDefs === 'object' ) {
			if(columnDefs?.visible === false) {
				td.style.display = 'none';
				td.setAttribute('style', 'display:none');
			}
		}

		td.dataset.colIndex = col.index;
        tr.appendChild(td);
      });

      tbody.appendChild(tr);

      if (this.options.responsive) {
        const childTr = document.createElement('tr');
        childTr.className = 'sdt-child-row';
        childTr.style.display = 'none';
        const childTd = document.createElement('td');
        childTd.colSpan = totalCols;
        const list = document.createElement('ul');
        list.className = 'sdt-child-list';
        this.columns.forEach(col => {
          const li = document.createElement('li');
          li.dataset.colIndex = col.index;
          const label = document.createElement('span');
          label.className = 'sdt-child-label';
          label.textContent = col.name + ' : ';
          const value = document.createElement('span');
          value.className = 'sdt-child-value';
		  
          if (row.__domRow) {
            value.innerHTML = row.html[col.index] != null ? row.html[col.index] : '';
          } else {
            const raw = row[col.data];
            value.innerHTML = typeof col.render === 'function' ? col.render(raw, row) : (raw != null ? raw : '');
          }

          li.appendChild(label);
          li.appendChild(value);
          list.appendChild(li);
        });
        childTd.appendChild(list);
        childTr.appendChild(childTd);
        tbody.appendChild(childTr);

        const toggle = tr.querySelector('.sdt-control-toggle');
        toggle.addEventListener('click', () => {
          const isOpen = childTr.style.display !== 'none';
          childTr.style.display = isOpen ? 'none' : 'table-row';
          toggle.classList.toggle('sdt-open', !isOpen);
        });
      }
    });
  }

  _applyResponsive() {
    // 1. tout réafficher pour mesurer les largeurs naturelles
    this.columns.forEach(col => {
      let display = true;
      const columnDefs = this.options.columnDefs.find(item => item.target === col.index);
      if(typeof columnDefs === 'object' ) {
        if(columnDefs?.visible === false) {
          display = false;
		}
      }

      if(display) {
        col.el.style.display = '';
        this.table.querySelectorAll(`[data-col-index="${col.index}"]`).forEach(cell => cell.style.display = '');
        this.table.querySelectorAll(`.sdt-child-list li[data-col-index="${col.index}"]`).forEach(li => li.style.display = 'none');
        col.hidden = false;
      }
    });

    const available = this.wrapper.clientWidth;
    let tableWidth = this.table.scrollWidth;

    if (tableWidth <= available) {
      this._toggleControlColumn(false);
      return;
    }

    // masque en priorité les colonnes les moins prioritaires (0 = jamais masquée)
    const candidates = this.columns
      .filter(col => col.priority > 0)
      .sort((a, b) => b.priority - a.priority);

    for (const col of candidates) {
      if (tableWidth <= available) break;
      const width = col.el.getBoundingClientRect().width;
      col.el.style.display = 'none';
      this.table.querySelectorAll(`[data-col-index="${col.index}"]`).forEach(cell => cell.style.display = 'none');
      this.table.querySelectorAll(`.sdt-child-list li[data-col-index="${col.index}"]`).forEach(li => li.style.display = '');
      col.hidden = true;
      tableWidth -= width;
    }

    this._toggleControlColumn(this.columns.some(col => col.hidden));
  }

  _toggleControlColumn(show) {
    if (this.controlTh) this.controlTh.style.display = show ? '' : 'none';
    this.table.querySelectorAll('.sdt-control-col').forEach(td => td.style.display = show ? '' : 'none');
    this.table.classList.toggle('sdt-has-hidden', show);
  }

  _renderInfo() {
    if (!this.infoEl) return;
    if (!this.state.recordsTotal) {
      this.infoEl.textContent = this.lang.infoEmpty;
      return;
    }
    const start = this.state.start + 1;
    const end = Math.min(this.state.start + this.state.length, this.state.recordsFiltered);
    let text = this.lang.info
      .replace('_START_', start)
      .replace('_END_', end)
      .replace('_TOTAL_', this.state.recordsFiltered);
    if (this.state.recordsFiltered < this.state.recordsTotal) {
      text += ' ' + this.lang.infoFiltered.replace('_MAX_', this.state.recordsTotal);
    }
    this.infoEl.textContent = text;
  }

  _renderPagination() {
    this.paginationEl.innerHTML = '';
    const totalPages = Math.max(1, Math.ceil(this.state.recordsFiltered / this.state.length));
    const currentPage = Math.max(1, Math.min(Math.floor(this.state.start / this.state.length) + 1, totalPages));

    // Recale state.start si jamais il pointait au-delà de la dernière page
    // (ex: le nombre de résultats a diminué après une recherche).
    const clampedStart = (currentPage - 1) * this.state.length;
    if (clampedStart !== this.state.start) this.state.start = clampedStart;

    const goToPage = (page) => {
      // On reclampe toujours ici au moment du clic, jamais sur une valeur
      // figée au rendu précédent : impossible de dépasser la dernière page.
      const target = Math.max(1, Math.min(page, totalPages));
      if (target === currentPage) return;
      this.state.start = (target - 1) * this.state.length;
      this._draw();
    };

    const makeBtn = (label, page, disabled, active) => {
      const btn = document.createElement('li');
      btn.disabled = disabled;
	  btn.classList.add('paginate_button', 'page-item');
	  
      const aEl = document.createElement('a');
      aEl.className = 'page-link';
	  aEl.textContent = label;
	  
      if (active) btn.classList.add('active');
      aEl.addEventListener('click', () => goToPage(page));
	  
      btn.appendChild(aEl); 

      return btn;
    };

    this.paginationEl.appendChild(makeBtn(this.lang.paginate.first, 1, currentPage === 1));
    this.paginationEl.appendChild(makeBtn(this.lang.paginate.previous, currentPage - 1, currentPage === 1));

    const windowSize = 2;
    const startPage = Math.max(1, currentPage - windowSize);
    const endPage = Math.min(totalPages, currentPage + windowSize);
    for (let p = startPage; p <= endPage; p++) {
      this.paginationEl.appendChild(makeBtn(p, p, false, p === currentPage));
    }

    this.paginationEl.appendChild(makeBtn(this.lang.paginate.next, currentPage + 1, currentPage === totalPages));
    this.paginationEl.appendChild(makeBtn(this.lang.paginate.last, totalPages, currentPage === totalPages));
  }

  // Permet de forcer un rechargement manuel (ex: après soumission d'un formulaire de filtre)
  reload(resetPaging = false) {
    if (resetPaging) this.state.start = 0;
    this._draw();
	return this;
  }

  /**
   * Définit le terme de recherche global, synchronise le champ de recherche
   * visible à l'écran, et remet la pagination à zéro.
   * Usage : dt.search('mot-clé').draw();
   */
  search(value) {
    this.state.search = value || '';
    if (this.searchInput) this.searchInput.value = this.state.search;
    this.state.start = 0;
    return this;
  }

  /**
   * Mode client-side uniquement (ajax: null) : remplace le tableau de données
   * et redessine. Usage : dt.setData(nouvellesLignes).
   */
  setData(newData, resetPaging = true) {
    this.options.data = Array.isArray(newData) ? newData : [];
    if (resetPaging) this.state.start = 0;
    this._draw();
    return this;
  }
 
  /**
   * Compatibilité avec l'appel `dt.rows({ search: '...' }).draw();`.
   * Ici on ne fait que ce dont tu as besoin : préremplir la recherche
   * globale à partir d'une valeur externe (ex: paramètre d'URL Twig).
   * Toute autre clé passée dans `options` est ignorée pour l'instant.
   */
  rows(options = {}) {
    if (options && typeof options.search === 'string') {
      this.search(options.search);
    }
    return this;
  }
 
  /**
   * Déclenche le rendu (équivalent de dt.draw() côté DataTables).
   * resetPaging=true force le retour à la première page.
   */
  draw(resetPaging = false) {
    if (resetPaging) this.state.start = 0;
	this._firstDrawDone = true;
    this._draw();
    return this;
  }
}
