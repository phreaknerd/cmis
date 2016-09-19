(function($){

  Drupal.behaviors.cmisField = {
    attach: function(context, settings) {
      //TODO: This selector doesn't seem right - will it work if there's a different default lang.?
      $(".cmis-field-insert", context).click(function() {
        if($.query['caller'] == 'settings') {
          var cmispath = $(this).attr('href');
          $('#edit-instance-settings-cmis-field-rootfolderpath', window.opener.document).val(cmispath.replace("//", "/"));
        }
        else {
          var cmispath = $(this).attr('id');
          var cmisname = $(this).attr('name');
          $('.edit-field-cmis-field').val(cmisname);
          $('.edit-field-cmis-path').val(cmispath);
        }
        var button = $(".ui-dialog button").click();
        return false;
      });
    }
  };
  
  $.query = (function(a) {
    if (a == "") return {};
    var b = {};
    for (var i = 0; i < a.length; ++i)
    {
        var p=a[i].split('=');
        b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
    }
    return b;
})(window.location.search.substr(1).split('&'))
})(jQuery);