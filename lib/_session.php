<?php
session_name('SimpleImageGallery_'.str_replace( ['v.','.'],['','_'], trim(file_get_contents('../version.txt')) ?? '' ) );

session_destroy();
?>