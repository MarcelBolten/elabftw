/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let ChemDoodle: any;
import tinymce from 'tinymce/tinymce';
import 'jquery-ui/ui/widgets/sortable';

interface ResponseMsg {
  res: boolean;
  msg: string;
  color?: string;
}

const moment = require('moment'); // eslint-disable-line @typescript-eslint/no-var-requires

// DISPLAY COMMENT TIME RELATIVE TO NOW
export function relativeMoment(): void {
  moment.locale($('#user-prefs').data('lang'));
  $.each($('.relative-moment'), function(i, el) {
    el.textContent = moment(el.title, 'YYYY-MM-DD H:m:s').fromNow();
  });
}

// PUT A NOTIFICATION IN TOP LEFT WINDOW CORNER
export function notif(info: ResponseMsg): void {
  const htmlText = '<p>' + info.msg + '</p>';
  let result = 'ko';
  if (info.res) {
    result = 'ok';
  }
  const overlay = document.createElement('div');
  overlay.setAttribute('id','overlay');
  overlay.setAttribute('class', 'overlay ' + 'overlay-' + result);
  // show the overlay
  document.body.appendChild(overlay);
  // add text inside
  document.getElementById('overlay').innerHTML = htmlText;
  // wait a bit and make it disappear
  window.setTimeout(function() {
    $('#overlay').fadeOut(763, function() {
      $(this).remove();
    });
  }, 2733);
}

// DISPLAY 2D MOL FILES
export function displayMolFiles(): void {
  // loop all the mol files and display the molecule with ChemDoodle
  $.each($('.molFile'), function() {
    // id of the canvas to attach the viewer to
    const id = $(this).attr('id');
    // now get the file content and display it in the viewer
    ChemDoodle.io.file.content($(this).data('molpath'), function(fileContent: string){
      const mol = ChemDoodle.readMOL(fileContent);
      const viewer = new ChemDoodle.ViewerCanvas(id, 250, 250);
      // config some stuff in the viewer
      viewer.specs.bonds_width_2D = 0.6; // eslint-disable-line @typescript-eslint/camelcase
      viewer.specs.bonds_saturationWidth_2D = 0.18; // eslint-disable-line @typescript-eslint/camelcase
      viewer.specs.bonds_hashSpacing_2D = 2.5; // eslint-disable-line @typescript-eslint/camelcase
      viewer.specs.atoms_font_size_2D = 10; // eslint-disable-line @typescript-eslint/camelcase
      viewer.specs.atoms_font_families_2D = ['Helvetica', 'Arial', 'sans-serif']; // eslint-disable-line @typescript-eslint/camelcase
      viewer.specs.atoms_displayTerminalCarbonLabels_2D = true; // eslint-disable-line @typescript-eslint/camelcase
      // load it
      viewer.loadMolecule(mol);
    });
  });
}
// for editXP/DB, ctrl-shift-D will add the date
export function addDateOnCursor(): void {
  const todayDate = new Date();
  const today = todayDate.toISOString().split('T')[0];
  tinymce.activeEditor.execCommand('mceInsertContent', false, today + ' ');
}

// called when you click the save button of tinymce
export function quickSave(type: string, id: string): void {
  $.post('app/controllers/EntityAjaxController.php', {
    quickSave: true,
    type : type,
    id : id,
    // we need this to get the updated content
    title : (document.getElementById('title_input') as HTMLInputElement).value,
    date : (document.getElementById('datepicker') as HTMLInputElement).value,
    body : tinymce.activeEditor.getContent()
  }).done(function(json, textStatus, xhr) {
    // detect if the session timedout
    if (xhr.getResponseHeader('X-Elab-Need-Auth') === '1') {
      // store the modifications in local storage to prevent any data loss
      localStorage.setItem('body', tinymce.activeEditor.getContent());
      localStorage.setItem('id', id);
      localStorage.setItem('type', type);
      localStorage.setItem('date', new Date().toLocaleString());
      // reload the page so user gets redirected to the login page
      location.reload();
      return;
    }
    notif(json);
  });
}

// insert a get param in the url and reload the page
export function insertParamAndReload(key: any, value: any): void {
  const params = new URLSearchParams(document.location.search.slice(1));
  params.set(key, value);
  // reload the page
  document.location.search = params.toString();
}

// SORTABLE ELEMENTS
export function makeSortableGreatAgain(): void {
  // need an axis and a table via data attribute
  $('.sortable').sortable({
    // limit to horizontal dragging
    axis : $(this).data('axis'),
    helper : 'clone',
    handle : '.sortableHandle',
    // we don't want the Create new pill to be sortable
    cancel: 'nonSortable',
    // do ajax request to update db with new order
    update: function() {
      // send the order as an array
      const ordering = $(this).sortable('toArray');
      $.post('app/controllers/SortableAjaxController.php', {
        table: $(this).data('table'),
        ordering: ordering
      }).done(function(json) {
        notif(json);
      });
    }
  });
}
