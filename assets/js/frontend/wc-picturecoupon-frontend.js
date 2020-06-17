const ProfilePicture = {
    changeAvatarPicture : ( id ) => {
        document.getElementById( "wcpc-replace-image" ).value = id;
        document.getElementById( "wcpc-replace-form" ).submit();
    }
}