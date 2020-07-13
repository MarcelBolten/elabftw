/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { notif } from './misc';
import i18next from 'i18next';

$(document).ready(function() {
  const id = $('#info').data('id');
  let type = $('#info').data('type');
  if (type === undefined) {
    type = 'experiments_templates';
  }

  class Tag {
    controller: string;


    constructor() {
      this.controller = 'app/controllers/TagsController.php';
    }

    saveForTemplate(tplId: number): void {
      // get tag
      const tag = $('#createTagInput_' + tplId).val();
      // POST request
      $.post(this.controller, {
        createTag: true,
        tag: tag,
        itemId: tplId,
        type: 'experiments_templates'
      }).done(function () {
        $('#tags_div_' + tplId).load(' #tags_div_' + tplId);
        // clear input field
        $('#createTagInput_' + tplId).val('');
      });
    }

    // REFERENCE A TAG
    save(): void {
      // get tag
      const tag = $('#createTagInput').val() as string;
      // do nothing if input is empty
      if (tag.length > 0) {
        // POST request
        $.post(this.controller, {
          createTag: true,
          tag: tag,
          itemId: id,
          type: type
        }).done(function(json) {
          notif(json);
          $('#tags_div').load('?mode=edit&id=' + id + ' #tags_div');
          // clear input field
          $('#createTagInput').val('');
        });
      }
    }

    // DEDUPLICATE
    deduplicate(tag: string): void {
      $.post(this.controller, {
        deduplicate: true,
        tag: tag
      }).done(function(json) {
        notif(json);
        $('#tag_manager').load(location + '?tab=9 #tag_manager');
      });
    }

    // REMOVE THE TAG FROM AN ENTITY
    unreference(tagId: number): void {
      if (confirm(i18next.t('tag-delete-warning'))) {
        $.post(this.controller, {
          unreferenceTag: true,
          type: type,
          itemId: id,
          tagId: tagId
        }).done(function() {
          $('#tags_div').load(location + '?mode=edit&id=' + id + ' #tags_div');
        });
      }
    }

    // REMOVE THE TAG FROM AN ENTITY
    unreferenceForTemplate(tagId: number, tplId: number): void {
      if (confirm(i18next.t('tag-delete-warning'))) {
        $.post(this.controller, {
          unreferenceTag: true,
          type: type,
          itemId: tplId,
          tagId: tagId
        }).done(function() {
          $('#tags_div_' + tplId).load(' #tags_div_' + tplId);
        });
      }
    }
    // REMOVE A TAG COMPLETELY (from admin panel/tag manager)
    destroy(tagId: number): void {
      if (confirm(i18next.t('tag-delete-warning'))) {
        $.post(this.controller, {
          destroyTag: true,
          tagId: tagId
        }).done(function() {
          $('#tag_manager').load(location + '?tab=9 #tag_manager');
        });
      }
    }
  }


  ///////
  // TAGS
  const TagC = new Tag();

  // CREATE for templates
  $(document).on('keypress blur', '.createTagInput', function(e) {
    // Enter is ascii code 13
    if ($(this).val() === '') {
      return;
    }
    if (e.which === 13 || e.type === 'focusout') {
      TagC.saveForTemplate($(this).data('id'));
    }
  });

  // listen keypress, add tag when it's enter or focus out
  $(document).on('keypress blur', '#createTagInput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      TagC.save();
    }
  });

  // AUTOCOMPLETE
  const cache = {};
  // # is for db or xp, . is for templates, should probably be homogeneized soon
  ($('#createTagInput, .createTagInput') as any).autocomplete({
    source: function(request: any, response: any) {
      const term  = request.term;
      if (term in cache) {
        response(cache[term]);
        return;
      }
      $.getJSON('app/controllers/TagsController.php', request, function(data) {
        cache[term] = data;
        response(data);
      });
    }
  });

  // DEDUPLICATE
  $(document).on('click', '.tagDeduplicate', function() {
    TagC.deduplicate($(this).data('tag'));
  });

  // UNREFERENCE (remove link between tag and entity)
  $(document).on('click', '.tagUnreference', function() {
    TagC.unreference($(this).data('tagid'));
  });
  $(document).on('click', '.tagUnreferenceForTemplate', function() {
    TagC.unreferenceForTemplate($(this).data('tagid'), $(this).data('id'));
  });

  // DESTROY (from admin panel/tag manager)
  $(document).on('click', '.tagDestroy', function() {
    TagC.destroy($(this).data('tagid'));

  });
});
