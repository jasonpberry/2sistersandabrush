var SortableUtils = {
  sortableFixOffsetTop: function(that) {
    if (!that.containment) { return that.offset.click.top; }

    var helperHeight = that.helperProportions.height;
    var containmentTop = that.containment[1];
    var containmentBottom = that.containment[3];
    var halfHelperHeight = helperHeight / 2;
    var relativeTop = that.positionAbs.top - containmentTop;
    var relativeBottom = containmentBottom - that.positionAbs.top;

    function lerp(y0, y1, x) {
      return y0 + (y1 - y0) * x;
    }

    if (relativeTop < helperHeight) {
      return lerp(0, halfHelperHeight, relativeTop / halfHelperHeight);
    } else if (relativeBottom < helperHeight) {
      return lerp(helperHeight, halfHelperHeight, relativeBottom / halfHelperHeight);
    } else {
      return halfHelperHeight;
    }
  },

  setSortableItems: function(row, onStartCallback, onStopCallback) {
    if (onStartCallback) {
      onStartCallback(row);
    }

    $('table.sortable')
        .on('mousedown touchstart', this.adjustTableHeight('static'))
        .on('mouseup', this.adjustTableHeight('auto'))
        .sortable(this.getSortableConfig(onStopCallback))
        .find('tr').disableSelection();
  },

  adjustTableHeight: function(height) {
    return function() {
      var $table = $(this).closest('table');
      if (height === 'static') {
        $table.data('pre-sort-height', $table.css('height'));
      }
      $table.css('height', height === 'static' ? $table.height() : $table.data('pre-sort-height'));
    };
  },

  getSortableConfig: function(onStopCallback) {
    return {
      forceHelperSize: true,
      axis: 'y',
      containment: 'parent',
      items: "tr:not(.ui-state-disabled)",
      tolerance: 'pointer',
      helper: function(event, ui) {
        return SortableUtils.fixedHelper(event, ui);
      },
      start: function(event, ui) {
        ui.placeholder.height(ui.item.height() - 1);
        $(this).sortable('refresh');
      },
      stop: function(event, ui) {
        if (onStopCallback) {
          onStopCallback(ui.item, this);
        }
      }
    };
  },

  fixedHelper: function(event, ui) {
    ui.children().each(function() {
      $(this).width($(this).width());
    });
    return ui;
  },

  initCustomSortable: function() {
    $.widget("ui.sortable", $.extend({}, $.ui.sortable.prototype, {
      _intersectsWithPointer: function(item) {
        var t = "x" === this.options.axis || this._isOverAxis(this.positionAbs.top + SortableUtils.sortableFixOffsetTop(this), item.top, item.height),
            i = "y" === this.options.axis || this._isOverAxis(this.positionAbs.left + this.offset.click.left, item.left, item.width),
            s = t && i,
            n = this._getDragVerticalDirection(),
            a = this._getDragHorizontalDirection();
        return s ? this.floating ? a && "right" === a || "down" === n ? 2 : 1 : n && ("down" === n ? 2 : 1) : !1
      }
    }));
  }
};

function initSortable(onStartCallback, onStopCallback) {
  SortableUtils.initCustomSortable();

  var draggable = $('.dragger').on('mousedown touchstart', function() {
    SortableUtils.setSortableItems(this, onStartCallback, onStopCallback);
  });

  $('body').on('mouseleave touchend', function() {
    draggable.trigger('mouseup');
  });
}

function updateDragSortOrder_forList(row, tableEl) {
  var rows = tableEl.tBodies[0].rows;
  var newOrder = "";
  for (var i = 0; i < rows.length; i++) {
    var order = $("._recordNum", rows[i]).val();
    if (order) {
      if (newOrder != "") { newOrder += ","; }
      newOrder += order;
    }
  }

  $('body').css('cursor', 'wait');
  $.ajax({
    url: '?',
    type: "POST",
    data: {
      menu: $(tableEl).data('table'),
      action: 'listDragSort',
      recordNums: newOrder,
      _CSRFToken: $('[name=_CSRFToken]').val()
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      alert("There was an error sending the request! (" + XMLHttpRequest['status'] + " " + XMLHttpRequest['statusText'] + ")");
    },
    success: function(msg) {
      $('body').css('cursor', 'default');
      if (msg) { alert("Error: " + msg); }
    }
  });
}
