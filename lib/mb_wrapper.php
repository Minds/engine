<?php

// if mb functions are available, set internal encoding to UTF8
if (is_callable('mb_internal_encoding')) {
	mb_internal_encoding("UTF-8");
//	ini_set("mbstring.internal_encoding", 'UTF-8');
}
