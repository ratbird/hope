PHP = php
PLESSC = $(PHP) vendor/mishal-iless/bin/iless
JLESSC = $(shell which lessc)
STYLES = public/assets/stylesheets
JAVA   = $(shell which java)

ifneq ($(wildcard $(JLESSC)),)
	LESSC = $(JLESSC)
else
	LESSC = $(PLESSC)
endif

build: less squeeze

squeeze: less force_update
	php cli/squeeze.php

doc: force_update
	doxygen Doxyfile

test: force_update
	vendor/bin/codecept run

# recipe to compile all .less files to CSS
less: $(STYLES)/style.css $(STYLES)/smiley.css

$(STYLES)/style.css: $(wildcard $(STYLES)/less/*.less)

%.css: %.less $(STYLES)/mixins.less $(wildcard $(STYLES)/mixins/*.less) 
	$(LESSC) $< $@

# dummy target to force update of "doc" target
force_update:
