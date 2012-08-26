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
less: $(STYLES)/style.css $(STYLES)/smiley.css

$(STYLES)/style.css: $(STYLES)/style.less $(STYLES)/mixins.less $(STYLES)/less/links.less $(STYLES)/less/tables.less $(STYLES)/less/content.less $(STYLES)/less/layouts.less $(STYLES)/less/header.less $(STYLES)/less/navigation.less $(STYLES)/less/infobox.less $(STYLES)/less/ajax.less $(STYLES)/less/autocomplete.less $(STYLES)/less/buttons.less $(STYLES)/less/messagebox.less $(STYLES)/less/quicksearch.less $(STYLES)/less/skiplinks.less $(STYLES)/less/tabs.less $(STYLES)/less/tooltip.less $(STYLES)/less/archiv.less $(STYLES)/less/calendar.less $(STYLES)/less/contacts.less $(STYLES)/less/evaluation.less $(STYLES)/less/study-area-selection.less $(STYLES)/less/wiki.less $(STYLES)/less/admin.less $(STYLES)/less/colors.less $(STYLES)/less/mobile.less
$(STYLES)/smiley.css: $(STYLES)/smiley.less

%.css: %.less
	$(LESSC) $< $@

# dummy target to force update of "doc" target
force_update:
