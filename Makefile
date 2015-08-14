PHP = php
PLESSC = $(PHP) vendor/mishal-iless/bin/iless
JLESSC = $(shell which lessc)
CODECEPT_VENDOR = $(shell which composer/bin/codecept)
CODECEPT = $(shell which codecept)
STYLES = public/assets/stylesheets
JAVA   = $(shell which java)

ifneq ($(wildcard $(JLESSC)),)
	LESSC = $(JLESSC)
else
	LESSC = $(PLESSC)
endif

ifneq ($(wildcard $(CODECEPT_VENDOR)),)
	RUN_TESTS = $(CODECEPT_VENDOR) run unit
else ifneq ($(wildcard $(CODECEPT)),)
	RUN_TESTS = $(CODECEPT) run unit
else
	RUN_TESTS = phpunit -c tests/phpunit.xml
endif


build: less squeeze

squeeze: less force_update
	php cli/squeeze.php

doc: force_update
	doxygen Doxyfile

test: force_update
	$(RUN_TESTS)

# recipe to compile all .less files to CSS
less: $(STYLES)/style.css $(STYLES)/studip-jquery-ui.css

$(STYLES)/style.css: $(wildcard $(STYLES)/less/*.less)
$(STYLES)/studip-jquery-ui.css: $(wildcard $(STYLES)/less/jquery-ui/*.less)

%.css: %.less $(STYLES)/mixins.less $(wildcard $(STYLES)/mixins/*.less) 
	$(LESSC) $< $@

# dummy target to force update of "doc" target
force_update:
