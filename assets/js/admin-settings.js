jQuery(document).ready(function($){
    // Kích hoạt WordPress Color Picker cho các trường có class 'reviewkit-color-field'
    if ( typeof $.fn.wpColorPicker !== 'undefined' ) {
        $('.reviewkit-color-field').wpColorPicker();
    }
});
