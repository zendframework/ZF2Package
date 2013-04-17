# Deployment makefile
#
# Extracts documentation tarballs to appropriate locations for use with website.
#
# Variables you can (and should) pass:
# - VERSION - the ZF version you want to extract documentation for
# - DOCS_PATH - the location where you want to extract documentation (default is
#   the location used on the ZF webserver)
# - TAR - path to the tar executable, if not on your $PATH environment
# 
# Targets available:
# - manual - extract api and end-user documentation
# - all - currently a synonym for manual

VERSION ?= false
DOCS_PATH ?= /var/local/framework

TAR ?= tar

VERSION_MAJOR := $(shell echo $(VERSION) | cut -f1 -d.)
VERSION_MINOR := $(shell echo $(VERSION) | cut -f2 -d.)

.PHONY : all manual manual-version

all : manual

manual : manual-version
	@echo "Extracting documentation for version $(VERSION)..."
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
	@echo "Extracting API documentation"
	-mkdir -p $(DOCS_PATH)/ZendFramework-$(VERSION)/apidoc $(DOCS_PATH)/ZendFramework-$(VERSION)/manual/en
	-(cd $(DOCS_PATH)/ZendFramework-$(VERSION)/apidoc && $(TAR) xzf $(CURDIR)/public/releases/ZendFramework-$(VERSION)/ZendFramework-$(VERSION)-apidoc.tgz --strip-components=1)
	@echo "Extracting manual documentation tarball"
	-(cd $(DOCS_PATH)/ZendFramework-$(VERSION)/manual/en && $(TAR) xzf $(CURDIR)/public/releases/ZendFramework-$(VERSION)/ZendFramework-$(VERSION)-manual-en.tgz)
	@echo "Re-linking documentation for $(VERSION_MAJOR).$(VERSION_MINOR)"
	-(cd $(DOCS_PATH) && rm -f ZendFramework-$(VERSION_MAJOR).$(VERSION_MINOR) && ln -s ZendFramework-$(VERSION) ZendFramework-$(VERSION_MAJOR).$(VERSION_MINOR))
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
