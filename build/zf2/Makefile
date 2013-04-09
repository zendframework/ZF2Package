# Makefile for ZF2 packages
#
# Parameters you should pass in:
#
# - PHP=<path to php> (if you want to specify a specific PHP binary)
# - VERSION=<version string> (required for end-user/api docs, zf2 tarball, and
#   pyrus!)
#
# You can override any given parameter by passing it in the command line or
# defining an equivalent ENV variable via export.

VERSION?=false

PHP=php
PHPDOC=$(CURDIR)/phpDocumentor.phar
TAR=tar
UNZIP=unzip
WGET=wget
ZIP=zip

COMPOSER=$(CURDIR)/composer.phar
PUBLIC=$(CURDIR)/../../public
PACKAGES=$(PUBLIC)/packages.json

EXPORT_HOME=$(CURDIR)/exports
RELEASE_HOME=$(PUBLIC)/releases
STAGE_HOME=$(CURDIR)/stage

.PHONY: clean

composer-vendor:
	@echo "Ensuring satis dependencies are installed..."
ifeq ($(wildcard vendor/autoload.php),)
	@echo "Installing composer dependencies for satis"
	-$(PHP) $(COMPOSER) install
	@if [ $$? -ne 0 ] ; then \
		echo "Composer installation failed" ; \
		exit 1 ; \
	fi
endif
	@echo "[DONE] Ensuring satis dependencies are installed."

composer: composer-vendor
	@echo "Running satis to create composer repository..."
	-$(PHP) $(CURDIR)/bin/satis build $(CURDIR)/satis.json $(PUBLIC)
	@echo "[DONE] Running satis to create composer repository."

version:
ifeq ("$(VERSION)", "false")
	@echo "Please pass the VERSION assignment for this target"
	exit 42
endif

zf2: zf2-archives zf2-docs zf2-release

zf2-export: version
	@echo "Exporting composer package for ZF version $(VERSION)..."
ifneq ($(wildcard $(EXPORT_HOME)/ZendFramework-$(VERSION)),)
	@echo "Composer package for ZF version $(VERSION) is already exported"
else
	$(eval ZIPPATH := $(shell $(PHP) "$(CURDIR)/bin/zf2-tarball-version.php" $(VERSION) $(PACKAGES)))
	@echo "Found and using composer package at $(ZIPPATH)"
	@$(UNZIP) -q -o -d $(EXPORT_HOME)/ZendFramework-$(VERSION) $(ZIPPATH)
endif
	@echo "[DONE] Exporting composer package for ZF version $(VERSION)."

zf2-archives: zf2-export
ifneq ($(wildcard $(STAGE_HOME)/ZendFramework-$(VERSION).tgz),)
	@echo "Zend Framework $(VERSION) is already staged"
else
	@echo "Creating TGZ archive"
	-(cd $(EXPORT_HOME) && $(TAR) czf $(STAGE_HOME)/ZendFramework-$(VERSION).tgz ZendFramework-$(VERSION))
	@echo "Creating ZIP archive"
	-(cd $(EXPORT_HOME) && $(ZIP) -rq $(STAGE_HOME)/ZendFramework-$(VERSION).zip ZendFramework-$(VERSION))
endif

zf2-docs: zf2-apidoc zf2-manual

zf2-apidoc: zf2-export
	@echo "Creating API documentation packages..."
ifneq ($(wildcard $(STAGE_HOME)/ZendFramework-$(VERSION)-apidoc.tgz),)
	@echo "API documentation packages already exist"
else
	$(eval PRODUCT := ZendFramework-$(VERSION))
	$(eval SOURCE_DIR := "$(EXPORT_HOME)/$(PRODUCT)/library")
	$(eval BUILD_DIR := "$(STAGE_HOME)/$(PRODUCT)-apidoc")
	-mkdir -p $(BUILD_DIR)
	@echo "Building API documentation"
	-$(PHP) -dmemory_limit=-1 $(PHPDOC) project:run \
		--verbose \
		--target $(BUILD_DIR) \
		--directory $(SOURCE_DIR) \
		--title "Zend Framework" \
		--template zf2
	@if [ $$? -ne 0 ] ; then \
		echo "[FAILED] Failed to build API documentation" ; \
		exit 1 ; \
	fi
	@echo "Packaging API documentation"
	-(cd $(STAGE_HOME) && $(TAR) czf $(PRODUCT)-apidoc.tgz $(PRODUCT)-apidoc)
	-(cd $(STAGE_HOME) && $(ZIP) -rq $(PRODUCT)-apidoc.zip $(PRODUCT)-apidoc)
endif
	@echo "[DONE] Creating API documentation packages."

exports/zf2-documentation-$(VERSION).tar.gz: version
ifeq ($(wildcard exports/zf2-documentation-$(VERSION).tar.gz),)
	@echo "Downloading ZF2 documentation for version $(VERSION)..."
	-$(WGET) https://github.com/zendframework/zf2-documentation/archive/release-$(VERSION).tar.gz -O exports/zf2-documentation-$(VERSION).tar.gz
	@if [ $$? -ne 0 ] ; then \
		echo "Failed retrieving ZF2 documentation for version $(VERSION)" ; \
		exit 1 ; \
	fi
	@echo "[DONE] Downloading ZF2 documentation for version $(VERSION)."
endif

zf2-export-manual: exports/zf2-documentation-$(VERSION).tar.gz
ifeq ($(wildcard exports/zf2-documentation-$(VERSION)),)
	@echo "Extracting manual..."
	$(eval DOCS_PATH := $(EXPORT_HOME)/zf2-documentation-$(VERSION))
	-mkdir $(DOCS_PATH)
	-(cd $(DOCS_PATH) && $(TAR) xzf ../zf2-documentation-$(VERSION).tar.gz --strip-components=1)
	@echo "[DONE] Extracting manual..."
endif

zf2-manual: zf2-export-manual
	@echo "Building and staging manual..."
	$(eval PRODUCT := ZendFramework-$(VERSION))
	$(eval DOCS_PATH := "$(EXPORT_HOME)/zf2-documentation-$(VERSION)/docs")
ifeq ($(wildcard stage/ZendFramework-$(VERSION)-manual-en.tgz),)
	@echo "Building manual..."
	-(cd $(DOCS_PATH) && $(MAKE) html latexpdf epub)
	@if [ $$? -ne 0 ] ; then \
		echo "Failed to build manual" ; \
		exit 1 ; \
	fi
	@echo "Packaging manual..."
	-(cd $(DOCS_PATH)/_build/html && $(ZIP) -rq $(STAGE_HOME)/$(PRODUCT)-manual-en.zip .)
	-(cd $(DOCS_PATH)/_build/html && $(TAR) czf $(STAGE_HOME)/$(PRODUCT)-manual-en.tgz .)
	-cp $(DOCS_PATH)/_build/latex/ZendFramework2.pdf $(STAGE_HOME)/$(PRODUCT)-manual-en.pdf
	-cp $(DOCS_PATH)/_build/epub/ZendFramework2.epub $(STAGE_HOME)/$(PRODUCT)-manual-en.epub
endif
	@echo "[DONE] Building and staging manual."

zf2-release: version
	@echo "Releasing Zend Framework $(VERSION)..."
	$(eval RELEASE_PATH := "$(RELEASE_HOME)/ZendFramework-$(VERSION)")
ifneq ($(wildcard $(RELEASE_PATH)),)
	@echo "Zend Framework $(VERSION) has already been released"
else
	-mkdir $(RELEASE_PATH)
	-cp $(STAGE_HOME)/ZendFramework*.tgz "$(RELEASE_PATH)/"
	-cp $(STAGE_HOME)/ZendFramework*.zip "$(RELEASE_PATH)/"
endif
	@echo "[DONE] Releasing Zend Framework $(VERSION)."

clean:
	-rm -f $(STAGE_HOME)/*.zip
	-rm -f $(STAGE_HOME)/*.tgz
	-rm -f $(STAGE_HOME)/*.epub
	-rm -f $(STAGE_HOME)/*.pdf
	-rm -Rf $(STAGE_HOME)/Zend*
	-rm -Rf $(EXPORT_HOME)/Zend*
	-rm -Rf $(EXPORT_HOME)/Component*
	-rm -Rf $(EXPORT_HOME)/ZF*
