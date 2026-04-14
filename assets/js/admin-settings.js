jQuery(document).ready(function($){
    // Kích hoạt WordPress Color Picker cho các trường có class 'mcwr-color-field'
    if ( typeof $.fn.wpColorPicker !== 'undefined' ) {
        $('.mcwr-color-field').wpColorPicker();
    }
});
