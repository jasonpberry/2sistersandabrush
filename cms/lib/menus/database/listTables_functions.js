//
document.addEventListener('DOMContentLoaded', () => {
    //
    multiRowSortingInit();

    // enable checking a range of checkboxes by holding shift
    checkboxRangeSelectorHandler(".selectRecordCheckbox");

    // enable toggle all checkbox that checks/unchecks all checkboxes
    checkboxUncheckAllHandler("#uncheckAllCheckbox", ".selectRecordCheckbox");


});

//
function multiRowSortingInit() {
    const sortableContainerEl = document.getElementById('sortable-tbody');
    const checkboxSelector = '.selectRecordCheckbox';

    // dynamically define styles
    const SortableStyles = document.createElement('style');
    SortableStyles.textContent = `
          /* Show blank row for drop zone */
          .sortable-ghost { visibility: hidden; } 
          
          /* Selected rows: show custom bgcolor and top/bottom borders */
          table.data { border-collapse: collapse; }
          tr.sortable-selected,
          table.table-striped > tbody > tr.sortable-selected > td,
          table.table-striped > tbody > tr.sortable-selected:hover > td {
              background-color: #CCDDF4;
              border-style: solid;
              border-width: 1px 0px;
              border-color: #BBB;              
          } 
          .sortable-drag, .sortable-drag > td  {
            /* Not used - without forceFallback browser visual defaults are difficult to override */
          }
    `;
    document.head.appendChild(SortableStyles);

    // init Sortable
    Sortable.create(sortableContainerEl, {
        multiDrag: true,                    // enable multi-row dragging
        draggable: 'tr',                    // specifies which items are sortable
        handle: '.dragger',                 // drag handle, also can be clicked to select/deselect
        selectedClass: 'sortable-selected', // identify selected rows that will move when dragged.  default: 'sortable-selected'
        ghostClass: 'sortable-ghost',       // class name for the placeholder that indicates where a dragged item will be dropped.  default: 'sortable-ghost'
        dragClass: 'sortable-drag',         // class name for the placeholder being dragged.  default: 'sortable-drag'
        fallbackTolerance: 3,               // So that we can select items on mobile
        animation: 300,
        //forceFallback: true,              // force fallback to 'clone' drag mode for all browsers - bypass HTML5 drag'n'drop support

        onSelect: function(evt) {  // Check our checkbox when row is selected by clicking Sortable .dragger handle
            evt.stopPropagation();  // Stops the event from bubbling up
            box = evt.item.querySelector(checkboxSelector);
            box.checked = true;
            const changeEvent = new Event('change', {'bubbles': true});
            box.dispatchEvent(changeEvent);
        },
        onDeselect: function(evt) { // Uncheck our checkbox when row is unselected by clicking Sortable .dragger handle
            // Note: Programmatically unchecking the checkbox after .dragger click doesn't work due to undetermined conflicts with Sortable library.
            // To get around this we're using a 0ms delay which performs the uncheck operation at the end of the event queue, after Sortable has executed.
            setTimeout(function() {
                evt.item.querySelector(checkboxSelector).checked = false;
            }, 0);
        },
        onMove: function (evt, originalEvent) { // Autoscroll window when dragging near top or bottom edge
            let mouseY          = originalEvent.clientY;
            let viewportHeight  = window.innerHeight;
            let pixelsFromEdge  = Math.min(mouseY, viewportHeight - mouseY); // min pixels from top or bottom edge
            let scrollDirection = pixelsFromEdge === mouseY ? -1 : 1; // Negative for up, positive for down
            let scrollAmount    = 30 * scrollDirection;

            // scroll faster when closer to edge
            if      (pixelsFromEdge <= 80)  { window.scrollBy(0, scrollAmount*10); } // approx height: 1 row
            else if (pixelsFromEdge <= 240) { window.scrollBy(0, scrollAmount); }       // approx height: 6 rows

            return true;
        },
        onEnd: function (evt) {
            // Refresh row state - After dropping sortable row states get unselected but our checkboxes remain checked so we need to update Sortable row state again
            // Note: Programmatically updating sortable row selected/unselected state doesn't work due to undetermined conflicts with Sortable library.
            // To get around this we're using a 0ms delay which performs the uncheck operation at the end of the event queue, after Sortable has executed.
            setTimeout(function() {
                const checkboxes = document.querySelectorAll(checkboxSelector);
                checkboxes.forEach((checkbox) => {
                    const tr = checkbox.closest('tr');
                    const method = checkbox.checked ? 'select' : 'deselect';
                    Sortable.utils[method](tr);
                });
            }, 0);

            // Get updated table order - collect table names from hidden input fields
            const tableNamesCSV = Array.from(document.querySelectorAll("input._tableName"))
                .map(input => input.value)
                .join(',');

            // Get Post data
            const params = new URLSearchParams();
            params.append('tableNames', tableNamesCSV);
            params.append('_CSRFToken', document.querySelector("input[name='_CSRFToken']").value);
            const postData = params.toString();

            // Send updated table order to server
            fetch('?menu=database&action=listTables&updateTableOrder=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: postData
            })
            .then(response => {
                if (!response.ok) { throw new Error(`Received HTTP ${response.status} ${response.statusText}`); }
                return response.text();  // This returns a Promise
            })
            .then(text => {
                if (text) { throw new Error(text); }
            })
            .catch((error) => {
                alert("Error: " + error);
            });

        } // end onEnd
    });

    // On checkbox click - Call Sortable select/deselect on related row
    sortableContainerEl.addEventListener('change', function(event) {
        if (!event.target.classList.contains('selectRecordCheckbox')) { return; }
        if (event.target.type !== 'checkbox') { return; } // skip for non-checkbox events
        const tr     = event.target.closest('tr');
        const method = event.target.checked ? 'select' : 'deselect';
        Sortable.utils[method](tr);
    });

}

/**
 * Handles enabling the controller checkbox and unchecking all checkboxes
 * when the controller checkbox is clicked.
 *
 * @param {string} controllerCheckboxSelector - The CSS selector for the controller checkbox.
 * @param {string} checkboxesSelector - The CSS selector for the checkboxes to be controlled.
 */
/**
 * Handles enabling the controller checkbox and unchecking all checkboxes
 * when the controller checkbox is clicked.
 *
 * @param {string} controllerCheckboxSelector - The CSS selector for the controller checkbox.
 * @param {string} checkboxesSelector - The CSS selector for the checkboxes to be controlled.
 */
/**
 * Handles enabling the controller checkbox and unchecking all checkboxes
 * when the controller checkbox is clicked.
 *
 * @param {string} controllerCheckboxSelector - The CSS selector for the controller checkbox.
 * @param {string} checkboxesSelector - The CSS selector for the checkboxes to be controlled.
 */
function checkboxUncheckAllHandler(controllerCheckboxSelector, checkboxesSelector) {
    const body = document.body;
    const controllerCheckbox = document.querySelector(controllerCheckboxSelector);
    const changeEvent = new Event('change', { 'bubbles': true });

    // Initialize the controller as disabled
    controllerCheckbox.disabled = true;

    body.addEventListener('change', function(event) {

        // Individual checkboxes logic
        if (event.target.matches(checkboxesSelector)) {
            let checkedCount = 0;
            const checkboxes = document.querySelectorAll(checkboxesSelector);
            checkboxes.forEach(function(box) {
                if (box.checked) checkedCount++;
            });

            // Enable the controller checkbox if some or all checkboxes are selected
            controllerCheckbox.disabled = (checkedCount === 0);

            // Set indeterminate state or checked state
            if (checkedCount === 0) {
                controllerCheckbox.indeterminate = false;
                controllerCheckbox.checked = false;
            } else if (checkedCount === checkboxes.length) {
                controllerCheckbox.indeterminate = false;
                controllerCheckbox.checked = true;
            } else {
                controllerCheckbox.indeterminate = true;
                controllerCheckbox.checked = false;
            }
        }

        // Controller checkbox logic
        else if (event.target.matches(controllerCheckboxSelector)) {
            document.querySelectorAll(checkboxesSelector).forEach(function(checkbox) {
                checkbox.checked = false;  // Uncheck all boxes
                checkbox.dispatchEvent(changeEvent);  // Trigger change event
            });

            // Disable the controller checkbox again
            controllerCheckbox.disabled = true;
            controllerCheckbox.indeterminate = false;
            controllerCheckbox.checked = false;
        }
    });
}




//
function confirmEraseTable(tableName) {

  var isConfirmed = confirm("Delete this menu?\n\nWARNING: All data will be lost!\n ");
  if (isConfirmed) {
//    window.location="?menu=database&action=editTable&dropTable=1&tableName=" + tableName;
    redirectWithPost('?', {
      'menu':       'database',
      'action':     'editTable',
      'dropTable':  '1',
      'tableName':  tableName,
      '_CSRFToken': $('[name=_CSRFToken]').val()
    });
  }
}

//
function addNewMenu(tablename, fieldname) {
  $('#addEditorModal').modal();
  resetAddTableFields();
}
