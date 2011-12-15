doc: force_update
	doxygen tools/Doxyfile

test: force_update
	phpunit -c test/phpunit.xml

# dummy target to force update of "doc" target
force_update:

