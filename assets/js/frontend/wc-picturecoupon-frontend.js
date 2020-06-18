const _wcpc = {
    changeAvatarPicture : ( id ) => {
        document.getElementById( "wcpc-replace-image" ).value = id;
        _wcpc.submit();
    },
    removeAvatarPicture : ( id ) => {
        document.getElementById( "wcpc-remove-image" ).value = id;
        _wcpc.submit();
    },
    submit : () => {
        document.getElementById( "wcpc-options-form" ).submit();
    }
}