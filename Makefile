# Deployment makefile

VERSION ?= false
DOCS_PATH ?= /var/local/framework

TAR ?= tar

VERSION_MAJOR := $(shell echo $(VERSION) | cut -f1 -d.)
VERSION_MINOR := $(shell echo $(VERSION) | cut -f2 -d.)

.PHONY : manual manual-version

manual : manual-version
	@echo "Extracting documentation for version $(VERSION)..."
	@echo "Major version: '$(VERSION_MAJOR)'"
	@echo "Minor version: '$(VERSION_MINOR)'"
ifeq "$(VERSION_MAJOR)" "1"
	@echo "Extracting version 1 documentation"
	@echo "Extracting API documentation"
	-(cd $(DOCS_PATH) && $(TAR) xzf $(CURDIR)/public/releases/ZendFramework-$(VERSION)/ZendFramework-$(VERSION)-apidoc.tar.gz)
	@for manual in $(CURDIR)/public/releases/ZendFramework-$(VERSION)/ZendFramework-$(VERSION)-manual-*.tar.gz ; do \
		echo "Extracting manual documentation tarball $$manual" ; \
		(cd $(DOCS_PATH) && tar xzf $$manual) ; \
	done
endif
ifeq "$(VERSION_MAJOR)" "2"
	@echo "Extracting version 2 documentation"
endif
	@echo "[DONE] Extracting documentation."

manual-version :
ifeq ($(VERSION),false)
	@echo "Missing VERSION assignment on commandline"
	exit 1
endif
ifeq ($(wildcard public/releases/ZendFramework-$(VERSION)),)
	@echo "No release packages found for VERSION $(VERSION)!"
	exit 1
endif
