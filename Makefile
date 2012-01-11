squeeze: force_update
	php cli/squeeze.php

doc: force_update
	doxygen tools/Doxyfile

test: force_update
	phpunit -c test/phpunit/phpunit.xml

# dummy target to force update of "doc" target
force_update:

