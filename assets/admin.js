(function($){
  let cropper=null, current=null;
  function aspectToRatio(val){ if(val==='landscape') return 1.91; if(val==='square') return 1; if(val==='portrait') return 0.8; return NaN; }
  function aspectToTarget(val){ if(val==='landscape') return '1200x628'; if(val==='square') return '1200x1200'; if(val==='portrait') return '1080x1350'; return 'auto'; }
  function updatePreview(){ var isCustom=$('input[name="text_mode"]:checked').val()==='custom'; var placeholder=$('textarea[name="custom_text"]').attr('placeholder')||''; var txt=isCustom? $('textarea[name="custom_text"]').val() : placeholder; $('.wpfpp-fb-preview .fb-text').text(txt||''); var c=$('.wpfpp-fb-preview .fb-images').empty(); $('.wpfpp-img img').each(function(){ $('<img>').attr('src',$(this).attr('src')).css({maxWidth:'120px',marginRight:'6px'}).appendTo(c); }); }
  function serializeOrder(){ var ids=[]; $('.wpfpp-img').each(function(){ ids.push($(this).data('id')); }); $('input[name="images[]"]').remove(); ids.forEach(function(id){ $('<input type="hidden" name="images[]"/>').val(id).appendTo('#wpfpp-image-picker'); }); }
  $(document).on('change','input[name="text_mode"]', updatePreview);
  $(document).on('keyup','textarea[name="custom_text"]', updatePreview);
  $('.wpfpp-sortable').sortable({ items:'.wpfpp-img', handle:'.wpfpp-drag', update: function(){ serializeOrder(); updatePreview(); } });
  $('#wpfpp-add-image').on('click', function(e){
    e.preventDefault();
    var frame=wp.media({title:'Select images',multiple:true});
    frame.on('select', function(){
      var sel=frame.state().get('selection');
      sel.each(function(att){
        if($('.wpfpp-img').length>=3) return;
        var id=att.get('id'), url=(att.get('sizes')&&att.get('sizes').medium)?att.get('sizes').medium.url:att.get('url');
        var div=$('<div class="wpfpp-img" data-id="'+id+'">\
          <span class="wpfpp-drag">⋮⋮</span>\
          <img src="'+url+'"/>\
          <div class="wpfpp-actions">\
            <label>Ratio:\
              <select class="wpfpp-ratio" data-id="'+id+'">\
                <option value="landscape" selected>Landscape 1.91:1</option>\
                <option value="square">Square 1:1</option>\
                <option value="portrait">Portrait 4:5</option>\
                <option value="free">Free</option>\
              </select>\
            </label>\
            <button type="button" class="button wpfpp-crop-btn" data-id="'+id+'">Crop</button>\
            <button type="button" class="button wpfpp-remove" data-id="'+id+'">&times;</button>\
          </div>\
          <input type="hidden" name="images[]" value="'+id+'">\
          <input type="hidden" name="crop['+id+']" value="">\
          <input type="hidden" name="cropmeta['+id+'][target]" class="wpfpp-crop-target" value="1200x628">\
        </div>');
        $('#wpfpp-image-picker').append(div);
      });
      serializeOrder(); updatePreview();
    });
    frame.open();
  });
  $(document).on('click','.wpfpp-remove', function(){ $(this).closest('.wpfpp-img').remove(); serializeOrder(); updatePreview(); });
  $(document).on('change', '.wpfpp-ratio', function(){ var id=$(this).data('id'); var val=$(this).val(); var target=aspectToTarget(val); $(this).closest('.wpfpp-img').find('.wpfpp-crop-target').val(target); if(current===id && cropper){ var ar=aspectToRatio(val); cropper.setAspectRatio(isNaN(ar)?NaN:ar); }});
  $(document).on('click','.wpfpp-crop-btn', function(){ var id=$(this).data('id'); current=id; var img=$(this).closest('.wpfpp-img').find('img').attr('src'); $('#wpfpp-cropper-img').attr('src',img); $('#wpfpp-cropper-modal').show(); var selectedRatio=$(this).closest('.wpfpp-img').find('.wpfpp-ratio').val(); $('#wpfpp-aspect').val(selectedRatio); if(cropper) cropper.destroy(); var ar=aspectToRatio(selectedRatio); cropper=new Cropper(document.getElementById('wpfpp-cropper-img'), {aspectRatio:isNaN(ar)?NaN:ar}); });
  $('#wpfpp-aspect').on('change', function(){ var v=$(this).val(); if(!cropper) return; var ar=aspectToRatio(v); cropper.setAspectRatio(isNaN(ar)?NaN:ar); if(current){ var card=$('.wpfpp-img[data-id="'+current+'"]'); card.find('.wpfpp-ratio').val(v).trigger('change'); } });
  $('#wpfpp-save-crop').on('click', function(){ if(!cropper||!current) return; var d=cropper.getData(true); $('input[name="crop['+current+']"]').val(JSON.stringify({x:Math.round(d.x),y:Math.round(d.y),w:Math.round(d.width),h:Math.round(d.height)})); $('#wpfpp-cropper-modal').hide(); });
  $('#wpfpp-cancel-crop').on('click', function(){ $('#wpfpp-cropper-modal').hide(); });
  $('form').on('submit', function(){ serializeOrder(); });
  $(document).ready(function(){ serializeOrder(); updatePreview(); });
})(jQuery);