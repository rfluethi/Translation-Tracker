/**
 * Translation Tracker – Frontend JS
 */

(function () {
  'use strict';

  const FIELDS = ['thumbnails', 'text', 'subtitles', 'exercise', 'quiz', 'audio', 'video'];

  // Status sort order (lower = better)
  const STATUS_ORDER = { done: 0, review: 1, wip: 2, open: 3, na: 4, '': 5 };

  var i18n         = {};
  var allLessons   = [];
  var activeFilter = 'all';
  var sortField    = 'order';   // default: course structure order
  var sortDir      = 1;         // 1 = asc, -1 = desc
  var groupState   = {};        // { groupId: true } = collapsed

  // ---- Init ----

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof ttData === 'undefined') return;

    i18n = ttData.i18n || {};

    if (ttData.error) {
      showError(ttData.error);
      return;
    }

    allLessons = ttData.lessons || [];
    renderLegend();
    updateInfo();
    ttRender();

    // Search — input event on the static search field.
    var searchEl = document.getElementById('tt-search');
    if (searchEl) {
      searchEl.addEventListener('input', function () { ttRender(); });
    }

    // Single delegated click handler — no inline onclick attributes needed.
    document.addEventListener('click', function (e) {
      var filterBtn = e.target.closest('.tt-filter-btn');
      if (filterBtn) { ttSetFilter(filterBtn.getAttribute('data-filter')); return; }

      var sortTh = e.target.closest('.tt-sortable');
      if (sortTh) { ttSort(sortTh.getAttribute('data-sort')); return; }

      var groupRow = e.target.closest('[data-toggle]');
      if (groupRow) { ttToggleGroup(groupRow.getAttribute('data-toggle')); }
    });
  });

  // ---- Filter ----

  function ttSetFilter(f) {
    activeFilter = f;
    document.querySelectorAll('.tt-filter-btn').forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-filter') === f);
    });
    ttRender();
  }

  // ---- Sort ----

  function ttSort(field) {
    if (sortField === field) {
      sortDir = -sortDir;
    } else {
      sortField = field;
      sortDir   = 1;
    }
    updateSortIndicators();
    ttRender();
  }

  function updateSortIndicators() {
    document.querySelectorAll('.tt-sortable').forEach(function (th) {
      var indicator = th.querySelector('.tt-sort-indicator');
      if (!indicator) return;
      if (th.getAttribute('data-sort') === sortField) {
        indicator.textContent = sortDir === 1 ? ' ▲' : ' ▼';
        th.classList.add('tt-sort-active');
      } else {
        indicator.textContent = '';
        th.classList.remove('tt-sort-active');
      }
    });
  }

  function compareBy(a, b, field) {
    if (field === 'title') {
      return a.title.localeCompare(b.title);
    }
    if (field === 'issue_number') {
      return a.issue_number - b.issue_number;
    }
    if (field === 'order') {
      var diff = (a.order || 9999) - (b.order || 9999);
      return diff !== 0 ? diff : a.title.localeCompare(b.title);
    }
    // Status field
    var sa = (a[field] && a[field].status) || '';
    var sb = (b[field] && b[field].status) || '';
    return (STATUS_ORDER[sa] ?? 5) - (STATUS_ORDER[sb] ?? 5);
  }

  // ---- Group tree ----

  /**
   * Build nested tree: pathway → course → section → [lessons]
   * Lessons without pathway go into a special '' bucket (rendered last as "Other").
   */
  function buildTree(lessons) {
    var tree = {};

    lessons.forEach(function (l) {
      var pw  = l.pathway  || '';
      var co  = l.course   || '';
      var sec = l.section  || '';

      if (!tree[pw])      tree[pw]      = {};
      if (!tree[pw][co])  tree[pw][co]  = {};
      if (!tree[pw][co][sec]) tree[pw][co][sec] = [];

      tree[pw][co][sec].push(l);
    });

    return tree;
  }

  // ---- Collapse/Expand ----

  function ttToggleGroup(id) {
    groupState[id] = !groupState[id];
    var rows = document.querySelectorAll('[data-group="' + id + '"]');
    rows.forEach(function (r) {
      r.classList.toggle('tt-group-hidden', !!groupState[id]);
    });
    var btn = document.querySelector('[data-toggle="' + id + '"] .tt-group-toggle');
    if (btn) btn.textContent = groupState[id] ? '▶' : '▼';
  };

  // ---- Render ----

  function ttRender() {
    var searchEl = document.getElementById('tt-search');
    var search   = searchEl ? searchEl.value.toLowerCase() : '';
    var tbody    = document.getElementById('tt-tbody');
    if (!tbody) return;

    // Filter lessons
    var filtered = allLessons.filter(function (l) {
      var matchSearch = !search || l.title.toLowerCase().indexOf(search) !== -1 ||
        (l.en_name && l.en_name.toLowerCase().indexOf(search) !== -1) ||
        (l.de_name && l.de_name.toLowerCase().indexOf(search) !== -1);
      var matchFilter = activeFilter === 'all' ||
        l.project_status === activeFilter;
      return matchSearch && matchFilter;
    });

    // Update stats bar: count per project status
    var total = filtered.length;
    var statusCounts = {};
    filtered.forEach(function (l) {
      var s = l.project_status || '';
      statusCounts[s] = (statusCounts[s] || 0) + 1;
    });

    var statsEl = document.getElementById('tt-stats');
    if (statsEl) {
      var parts = ['<strong>' + total + '</strong>&nbsp;' + esc(i18n.stat_lessons || 'Translations')];
      var statLabels = [
        ['Looking for Translator',  i18n.filter_looking              || 'Looking for Translator'],
        ['Awaiting Triage',         i18n.filter_awaiting             || 'Awaiting Triage'],
        ['Translation in Progress', i18n.filter_in_progress          || 'Translation in Progress'],
        ['Ready for Review',        i18n.filter_ready_for_review     || 'Ready for Review'],
        ['Preparing to Publish',    i18n.filter_preparing_to_publish || 'Preparing to Publish'],
        ['Published or Closed',     i18n.filter_published            || 'Published or Closed'],
      ];
      statLabels.forEach(function (pair) {
        var key = pair[0], label = pair[1];
        if (statusCounts[key]) {
          parts.push('<strong>' + statusCounts[key] + '</strong>&nbsp;' + esc(label));
        }
      });
      statsEl.innerHTML = '<div class="tt-stat-bar">' + parts.join('&ensp;&middot;&ensp;') + '</div>';
    }

    if (filtered.length === 0) {
      tbody.innerHTML = '<tr><td colspan="10" class="tt-empty">' +
        esc(i18n.no_lessons || 'No translations found.') + '</td></tr>';
      return;
    }

    // When user has clicked a column header, render flat sorted list
    if (sortField !== 'order') {
      renderFlat(filtered, tbody);
      return;
    }

    // Default: render grouped by course structure
    renderGrouped(filtered, tbody);
  };

  /**
   * Flat render (used when user sorts by a column other than order).
   */
  function renderFlat(lessons, tbody) {
    var sorted = lessons.slice().sort(function (a, b) {
      return sortDir * compareBy(a, b, sortField);
    });

    var html = '';
    sorted.forEach(function (l) {
      html += lessonRow(l);
    });
    tbody.innerHTML = html;
  }

  /**
   * Grouped render: pathway → course → section → lessons (sorted by order).
   */
  function renderGrouped(lessons, tbody) {
    var tree = buildTree(lessons);

    // Separate named pathways from the "" (Other) bucket
    var pathways = Object.keys(tree).filter(function (k) { return k !== ''; }).sort();
    var hasOther = tree.hasOwnProperty('');
    if (hasOther) pathways.push(''); // Other always last

    var html = '';

    pathways.forEach(function (pw) {
      var pwLabel = pw || esc(i18n.group_other || 'Other');
      var courses  = Object.keys(tree[pw]);
      var namedCourses  = courses.filter(function (k) { return k !== ''; }).sort();
      var hasOtherCourse = tree[pw].hasOwnProperty('');
      if (hasOtherCourse) namedCourses.push('');

      // Count lessons in this pathway
      var pwCount = countLessons(tree[pw]);

      // Pathway header (depth 0) – only if more than one pathway exists or pathway is named
      var pwId = 'pw-' + slugify(pw || '_other');
      if (pathways.length > 1 || pw !== '') {
        html += groupHeaderRow(pwLabel, pwCount, pwId, 0);
      }

      namedCourses.forEach(function (co) {
        var coLabel  = co || esc(i18n.group_other || 'Other');
        var sections = Object.keys(tree[pw][co]);
        var namedSecs  = sections.filter(function (k) { return k !== ''; }).sort();
        var hasOtherSec = tree[pw][co].hasOwnProperty('');
        if (hasOtherSec) namedSecs.push('');

        var coCount = countLessons(tree[pw][co]);
        var coId    = pwId + '-co-' + slugify(co || '_other');
        var coDepth = (pathways.length > 1 || pw !== '') ? 1 : 0;

        // Course header
        var coGroupAttr = (pathways.length > 1 || pw !== '') ? pwId : '';
        html += groupHeaderRow(coLabel, coCount, coId, coDepth, coGroupAttr);

        namedSecs.forEach(function (sec) {
          var secLabel   = sec || '';
          var secLessons = tree[pw][co][sec].slice().sort(function (a, b) {
            return compareBy(a, b, 'order');
          });
          var secCount = secLessons.length;
          var secDepth = coDepth + 1;
          var secId    = coId + '-sec-' + slugify(sec || '_other');

          if (namedSecs.length > 1 || sec !== '') {
            // Section header
            html += groupHeaderRow(secLabel, secCount, secId, secDepth, coId);

            secLessons.forEach(function (l) {
              html += lessonRow(l, secId);
            });
          } else {
            // No section header – render lessons directly under course
            secLessons.forEach(function (l) {
              html += lessonRow(l, coId);
            });
          }
        });
      });
    });

    tbody.innerHTML = html;
  }

  function groupHeaderRow(label, count, id, depth, parentGroupId) {
    var groupAttr  = parentGroupId ? ' data-group="' + parentGroupId + '"' : '';
    var collapsed  = !!groupState[id];
    var toggleChar = collapsed ? '▶' : '▼';
    var statsHtml  = '<span class="tt-group-count">' + count + '&nbsp;' +
      esc(i18n.stat_lessons || 'Translations') + '</span>';

    return '<tr class="tt-group-header tt-group-depth-' + depth + '"' + groupAttr +
      ' data-toggle="' + id + '">' +
      '<td colspan="10"><span class="tt-group-toggle">' + toggleChar + '</span>' +
      escHtml(label) + statsHtml + '</td></tr>';
  }

  function lessonRow(l, groupId) {
    var groupAttr = groupId ? ' data-group="' + groupId + '"' : '';
    var hidden    = (groupId && groupState[groupId]) ? ' tt-group-hidden' : '';

    var noTable   = !l.hasTable
      ? '<span class="tt-no-table">(' + esc(i18n.no_status_table || 'no status table') + ')</span>'
      : '';
    var stateText = l.issue_state === 'closed'
      ? esc(i18n.issue_closed || 'closed')
      : esc(i18n.issue_open   || 'open');

    // TV / YouTube sub-links
    var mediaLinks = '';
    if (l.tv_url) {
      mediaLinks += '<a class="tt-media-link" href="' + escHtml(l.tv_url) + '" target="_blank">' +
        esc(i18n.tv || 'WordPress.tv') + '</a>';
    }
    if (l.youtube_url) {
      if (mediaLinks) mediaLinks += '<span class="tt-media-sep">·</span>';
      mediaLinks += '<a class="tt-media-link" href="' + escHtml(l.youtube_url) + '" target="_blank">' +
        esc(i18n.youtube || 'YouTube') + '</a>';
    }
    var mediaRow = mediaLinks ? '<span class="tt-media-links">' + mediaLinks + '</span>' : '';

    // English lesson cell
    var enName = l.en_name || '';
    var enLink;
    if (enName && l.en_url) {
      enLink = '<a href="' + escHtml(l.en_url) + '" target="_blank">' + escHtml(enName) + '</a>';
    } else if (enName) {
      enLink = escHtml(enName);
    } else {
      enLink = '<em>' + escHtml(l.title) + '</em>';
    }

    // German lesson cell
    var deName = l.de_name || '';
    var deLink;
    if (deName && l.de_url) {
      deLink = '<a href="' + escHtml(l.de_url) + '" target="_blank">' + escHtml(deName) + '</a>';
    } else if (deName) {
      deLink = escHtml(deName);
    } else {
      deLink = '<em>' + escHtml(l.title) + '</em>';
    }

    return '<tr class="tt-lesson-row' + hidden + '"' + groupAttr + '>' +
      '<td class="tt-lesson-name">' + enLink + mediaRow + '</td>' +
      '<td class="tt-lesson-name">' + deLink + mediaRow + '</td>' +
      '<td><a class="tt-issue-link" href="' + escHtml(l.issue_url) + '" target="_blank">#' + l.issue_number + '</a>' +
        '<span class="tt-issue-sub">' + stateText + (noTable ? '<br>' + noTable : '') + '</span></td>' +
      '<td class="tt-status-col">' + statusBadge(l.thumbnails) + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.text)       + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.subtitles)  + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.exercise)   + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.quiz)       + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.audio)      + '</td>' +
      '<td class="tt-status-col">' + statusBadge(l.video)      + '</td>' +
    '</tr>';
  }

  // ---- Helpers ----

  function countLessons(node) {
    // node is either { section: [lessons] } or { course: { section: [lessons] } }
    var count = 0;
    Object.keys(node).forEach(function (k) {
      var v = node[k];
      if (Array.isArray(v)) {
        count += v.length;
      } else {
        count += countLessons(v);
      }
    });
    return count;
  }

  function slugify(str) {
    return String(str).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  }

  function personLink(username) {
    if (!username) return '';
    return '<a href="https://github.com/' + escHtml(username) + '" target="_blank">@' + escHtml(username) + '</a>';
  }

  function statusBadge(field) {
    if (!field || !field.status) return '';
    var s = field.status;

    var labelMap = {
      done:   i18n.status_done   || 'Done',
      review: i18n.status_review || 'Review',
      wip:    i18n.status_wip    || 'In Progress',
      open:   i18n.status_open   || 'Open',
      na:     i18n.status_na     || '—'
    };
    var label = labelMap[s] || s;

    var tip = '';
    if (field.creator || field.reviewer) {
      tip = '<span class="tt-tip">';
      if (field.creator)  tip += '<span class="tt-tip-row"><span class="tt-tip-label">C:</span> ' + personLink(field.creator)  + '</span>';
      if (field.reviewer) tip += '<span class="tt-tip-row"><span class="tt-tip-label">R:</span> ' + personLink(field.reviewer) + '</span>';
      tip += '</span>';
    }

    return '<span class="tt-badge tt-' + s + '">' + esc(label) + tip + '</span>';
  }

  function renderLegend() {
    var el = document.getElementById('tt-legend');
    if (!el) return;
    // Component-level status badge legend
    el.innerHTML =
      '<span class="tt-legend-item"><span class="tt-dot tt-done"></span>&nbsp;'   + esc(i18n.status_done   || 'Done')        + '</span>' +
      '<span class="tt-legend-item"><span class="tt-dot tt-review"></span>&nbsp;' + esc(i18n.status_review || 'Review')      + '</span>' +
      '<span class="tt-legend-item"><span class="tt-dot tt-wip"></span>&nbsp;'    + esc(i18n.status_wip   || 'In Progress') + '</span>' +
      '<span class="tt-legend-item"><span class="tt-dot tt-open"></span>&nbsp;'   + esc(i18n.status_open  || 'Open')        + '</span>' +
      '<span class="tt-legend-item"><span class="tt-dot tt-na"></span>&nbsp;'     + esc(i18n.status_na    || 'n/a')         + '</span>';
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
  }

  function esc(str) {
    return escHtml(str);
  }

  function showError(msg) {
    var el = document.getElementById('tt-info');
    if (el) {
      el.innerHTML = '<div class="tt-error">' + escHtml(msg) + '</div>';
    }
  }

  function updateInfo() {
    var el = document.getElementById('tt-info');
    if (!el) return;
    var withTable = allLessons.filter(function (l) { return l.hasTable; }).length;
    var without   = allLessons.length - withTable;
    el.innerHTML =
      '<strong>' + allLessons.length + '</strong> ' + esc(i18n.info_loaded || 'issues loaded') + ' &middot; ' +
      '<strong>' + withTable + '</strong> ' + esc(i18n.info_with_table || 'with status table') + ' &middot; ' +
      '<strong>' + without   + '</strong> ' + esc(i18n.info_without_table || 'without table');
  }

})();
