PHP = php
PLESSC = $(PHP) vendor/lessphp/plessc
JLESSC = $(shell which lessc)
STYLES = public/assets/stylesheets

ifneq ($(wildcard $(JLESSC)),)
	LESSC = $(JLESSC)
else
	LESSC = $(PLESSC)
endif

build: less squeeze

squeeze: force_update
	php cli/squeeze.php

doc: force_update
	doxygen tools/Doxyfile

test: force_update
	phpunit -c test/phpunit/phpunit.xml

# recipe to compile all .less files to CSS
less: $(shell find $(STYLES) -name '*.css')

%.css: %.less
	$(LESSC) $< $@

# dummy target to force update of "doc" target
force_update:
