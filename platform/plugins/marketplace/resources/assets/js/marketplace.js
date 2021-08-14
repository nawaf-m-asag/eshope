$(document).ready(function () {
    $('#shop-url')
        .on('keyup', function () {
            let displayURL = $(this).closest('.form-group').find('span small');
            displayURL.html(displayURL.data('base-url') + '/<strong>' + $(this).val().toLowerCase() + '</strong>');
        })
        .on('change', function () {
            $('.shop-url-wrapper').addClass('content-loading');
            $(this).closest('form').find('button[type=submit]').addClass('btn-disabled').prop('disabled', true);

            $.ajax({
                url: $(this).data('url'),
                type: 'POST',
                data: {
                    url: $(this).val(),
                },
                success: res => {
                    $('.shop-url-wrapper').removeClass('content-loading');
                    if (res.error) {
                        $('.shop-url-status').removeClass('text-success').addClass('text-danger').text(res.message);

                    } else {
                        $('.shop-url-status').removeClass('text-danger').addClass('text-success').text(res.message);
                        $(this).closest('form').find('button[type=submit]').prop('disabled', false).removeClass('btn-disabled');
                    }
                },
                error: () => {
                    $('.shop-url-wrapper').removeClass('content-loading');
                }
            });
        });

    $('.custom-select-image').on('click', function (event) {
        event.preventDefault();
        $(this).closest('.image-box').find('.image_input').trigger('click');
    });

    function readURL(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $(input).closest('.image-box').find('.preview_image').prop('src', e.target.result);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    $('.image_input').on('change', function () {
        readURL(this);
    });

});
