# dummy target to force update of "doc" target
force_update:

doc: force_update
	doxygen tools/Doxyfile
