window.addEventListener('load', function(){
    $('body').on('click', '#addProductFlag', function() {
        $('#availableProductFlags option:selected').each(function () {
            $('#selectedProductFlags').append("<option value='" + $(this).val() + "'>" + $(this).text() + '</option>');
            $(this).remove();
          });
          $('#selectedProductFlags option').prop('selected', true);
    });

    $('body').on('click', '#removeProductFlag', function() {
        $('#selectedProductFlags option:selected').each(function () {
            $('#availableProductFlags').append("<option value='" + $(this).val() + "'>" + $(this).text() + '</option>');
            $(this).remove();
          });
          $('#selectedProductFlags option').prop('selected', true);
    });
});