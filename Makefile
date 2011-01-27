doc: force_update
	doxygen tools/Doxyfile

test: force_update
	php test/all_tests.php

# dummy target to force update of "doc" target
force_update:

