# Makefile for ZF2 packages
#
# Parameters you should pass in:
#
# - PHP=<path to php> (if you want to specify a specific PHP binary)
# - VERSION=<version string> (required for end-user/api docs, zf2 tarball, and
#   pyrus!)
# - COMPONENTS=<space separated list of component names> (to override the
#   defaults, for instance, to release a subset of components)
# - SERVICES=<space separated list of service names> (to override the
#   defaults, for instance, to release a single service)
#
# You can override any given parameter by passing it in the command line or
# defining an equivalent ENV variable via export.

VERSION?=false

COMPONENTS?=Zend_Authentication Zend_Barcode Zend_Cache Zend_Captcha Zend_Code Zend_Config Zend_Console Zend_Crypt Zend_Db Zend_Debug Zend_Di Zend_Dom Zend_Escaper Zend_EventManager Zend_Feed Zend_File Zend_Filter Zend_Form Zend_Http Zend_I18n Zend_InputFilter Zend_Json Zend_Ldap Zend_Loader Zend_Log Zend_Mail Zend_Math Zend_Memory Zend_Mime Zend_ModuleManager Zend_Mvc Zend_Navigation Zend_Paginator Zend_Permissions_Acl Zend_Permissions_Rbac Zend_Progressbar Zend_Serializer Zend_Server Zend_ServiceManager Zend_Session Zend_Soap Zend_Stdlib Zend_Tag Zend_Test Zend_Text Zend_Uri Zend_Validator Zend_Version Zend_View Zend_Xmlrpc

SERVICES?=ZendCloud ZendGData ZendOAuth ZendOpenId ZendPdf ZendQueue ZendRest ZendService_AgileZen ZendService_Akismet ZendService_Amazon ZendService_Apple_Apns ZendService_Audioscrobbler ZendService_Delicious ZendService_DeveloperGarden ZendService_Flickr ZendService_GoGrid ZendService_Google_Gcm ZendService_LiveDocx ZendService_Nirvanix ZendService_Rackspace ZendService_ReCaptcha ZendService_SlideShare ZendService_StrikeIron ZendService_Technorati ZendService_Twitter ZendService_WindowsAzure

PHP?=php
PHPDOC?=$(CURDIR)/bin/phpDocumentor.phar
PYRUS?=$(CURDIR)/bin/pyrus.phar
TAR?=tar
UNZIP?=unzip
WGET?=wget
ZIP?=zip

COMPOSER=$(CURDIR)/bin/composer.phar
PUBLIC=$(CURDIR)/../../public
PACKAGES=$(PUBLIC)/packages.json

HOMEPAGE_TEMPLATE=$(CURDIR)/index.template.html
EXPORT_HOME=$(CURDIR)/exports
PYRUS_CHANNEL?=packages.zendframework.com
PYRUS_COMMAND=$(PHP) -dphar.readonly=0 $(PYRUS)
PYRUS_TEMPLATES?=$(CURDIR)/pyrus-templates
PYRUS_META_TEMPLATES?=$(CURDIR)/pyrus-meta-templates
RELEASE_HOME=$(PUBLIC)/releases
STAGE_HOME=$(CURDIR)/stage
PYRUS_HOME=$(STAGE_HOME)/pyrus

.PHONY: clean composer-vendor composer version zf2 zf2-export zf2-archives zf2-docs zf2-apidoc zf2-export-manual zf2-manual zf2-release pyrus-verify pyrus-export release pyrus-release pyrus pyrus-zf2

all: composer

zf2: zf2-archives zf2-minimal-archives zf2-docs pyrus

release: zf2-release pyrus-release

# Build Pyrus package per component
define ComponentPyrusBuildRule
dst=$1
$$(dst) : zf2-export pyrus-verify $(PYRUS_HOME)
	@echo "Building pyrus package for $$@..."
ifeq ($(wildcard stage/pyrus/$$@),)
	@echo "Generating package"
	-(cd $(PYRUS_HOME) && $(PYRUS_COMMAND) generate-pear2 $$@ $(PYRUS_CHANNEL))
endif
	-$(CURDIR)/bin/pyrus-stage-package.sh $$@ $(VERSION) $(PHP) $(TAR) $(CURDIR) $(EXPORT_HOME)/ZendFramework-$(VERSION) $(PYRUS_HOME) $(PYRUS_TEMPLATES)
ifeq ($$?,0)
	@echo "Failed to stage Pyrus package"
	exit 1
endif
	-$(CURDIR)/bin/pyrus-make.sh $$@ "$(PYRUS_COMMAND)" $(PYRUS_HOME)
ifeq ($$?,0)
	@echo "Failed to compile Pyrus package"
	exit 1
endif
	-mv $(PYRUS_HOME)/$$@/$$@-$(VERSION).phar $(PYRUS_HOME)/
	-rm -Rf $(PYRUS_HOME)/$$@/
	@echo "[DONE] Building pyrus package for $$@..."
endef
$(foreach component, $(COMPONENTS),$(eval $(call ComponentPyrusBuildRule,$(component))))

# Build Pyrus package per service component
define ServicePyrusBuildRule
dst=$1
$$(dst) : pyrus-verify $(PYRUS_HOME) version
	@echo "Building pyrus package for $$@..."
ifeq ($(wildcard exports/$$@-$(VERSION)),)
	@echo "Exporting package"
	-$(CURDIR)/bin/export-package.sh $$@ $(VERSION) $(PACKAGES) $(PHP) $(UNZIP) $(CURDIR) $(EXPORT_HOME)
ifeq ($$?,0)
	@echo "Failed to export package; check the version"
	exit 1
endif
endif
ifeq ($(wildcard stage/pyrus/$$@),)
	@echo "Generating package"
	-(cd $(PYRUS_HOME) && $(PYRUS_COMMAND) generate-pear2 $$@ $(PYRUS_CHANNEL))
endif
	-$(CURDIR)/bin/pyrus-stage-package.sh $$@ $(VERSION) $(PHP) $(TAR) $(CURDIR) $(EXPORT_HOME)/$$@-$(VERSION) $(PYRUS_HOME) $(PYRUS_TEMPLATES)
ifeq ($$?,0)
	@echo "Failed to stage Pyrus package"
	exit 1
endif
	-$(CURDIR)/bin/pyrus-make.sh $$@ "$(PYRUS_COMMAND)" $(PYRUS_HOME)
ifeq ($$?,0)
	@echo "Failed to compile Pyrus package"
	exit 1
endif
	-mv $(PYRUS_HOME)/$$@/$$@-$(VERSION).phar $(PYRUS_HOME)/
	-rm -Rf $(PYRUS_HOME)/$$@/
	@echo "[DONE] Building pyrus package for $$@..."
endef
$(foreach component, $(SERVICES),$(eval $(call ServicePyrusBuildRule,$(component))))

composer-vendor:
	@echo "Ensuring satis dependencies are installed..."
ifeq ($(wildcard vendor/autoload.php),)
	@echo "Installing composer dependencies for satis"
	-$(PHP) $(COMPOSER) install
ifeq ($$?,0)
	@echo "Composer installation failed"
	exit 1
endif
endif
	@echo "[DONE] Ensuring satis dependencies are installed."

composer: composer-vendor
	@echo "Running satis to create composer repository..."
	-$(PHP) $(CURDIR)/bin/satis build $(CURDIR)/satis.json $(PUBLIC)
	@echo "Updating home page"
	-$(PHP) $(CURDIR)/bin/composer-homepage.php $(HOMEPAGE_TEMPLATE) $(PUBLIC)/index.html $(PACKAGES)
ifeq ($$?,0)
	@echo "Failed to update homepage!"
	exit 1
endif
	@echo "[DONE] Running satis to create composer repository."

version:
ifeq ("$(VERSION)", "false")
	@echo "Please pass the VERSION assignment for this target"
	exit 42
endif

zf2-export: version
	@echo "Exporting composer package for ZF version $(VERSION)..."
ifneq ($(wildcard $(EXPORT_HOME)/ZendFramework-$(VERSION)),)
	@echo "Composer package for ZF version $(VERSION) is already exported"
else
	$(eval ZIPPATH := $(shell $(PHP) "$(CURDIR)/bin/package-version-path.php" "zendframework/zendframework" $(VERSION) $(PACKAGES)))
	@echo "Found and using composer package at $(ZIPPATH)"
	@$(UNZIP) -q -o -d $(EXPORT_HOME)/ZendFramework-$(VERSION) $(ZIPPATH)
endif
	@echo "[DONE] Exporting composer package for ZF version $(VERSION)."

zf2-minimal-export: zf2-export
	@echo "Exporting minimal distribution for ZF version $(VERSION)..."
ifneq ($(wildcard $(EXPORT_HOME)/ZendFramework-minimal-$(VERSION)),)
	@echo "Minimal distribution for ZF version $(VERSION) is already exported"
else
	-cp -a $(EXPORT_HOME)/ZendFramework-$(VERSION) $(EXPORT_HOME)/ZendFramework-minimal-$(VERSION)
	-rm -Rf $(EXPORT_HOME)/ZendFramework-minimal-$(VERSION)/demos
endif
	@echo "[DONE] Exporting minimal distribution for ZF version $(VERSION)."

zf2-archives: zf2-export
ifneq ($(wildcard $(STAGE_HOME)/ZendFramework-$(VERSION).tgz),)
	@echo "Zend Framework $(VERSION) is already staged"
else
	@echo "Creating TGZ archive"
	-(cd $(EXPORT_HOME) && $(TAR) czf $(STAGE_HOME)/ZendFramework-$(VERSION).tgz ZendFramework-$(VERSION))
	@echo "Creating ZIP archive"
	-(cd $(EXPORT_HOME) && $(ZIP) -rq $(STAGE_HOME)/ZendFramework-$(VERSION).zip ZendFramework-$(VERSION))
endif

zf2-minimal-archives: zf2-minimal-export
ifneq ($(wildcard $(STAGE_HOME)/ZendFramework-minimal-$(VERSION).tgz),)
	@echo "Zend Framework minimal $(VERSION) is already staged"
else
	@echo "Creating TGZ archive"
	-(cd $(EXPORT_HOME) && $(TAR) czf $(STAGE_HOME)/ZendFramework-minimal-$(VERSION).tgz ZendFramework-minimal-$(VERSION))
	@echo "Creating ZIP archive"
	-(cd $(EXPORT_HOME) && $(ZIP) -rq $(STAGE_HOME)/ZendFramework-minimal-$(VERSION).zip ZendFramework-minimal-$(VERSION))
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
ifeq ($$?,0)
	@echo "[FAILED] Failed to build API documentation"
	exit 1
endif
	@echo "Packaging API documentation"
	-(cd $(STAGE_HOME) && $(TAR) czf $(PRODUCT)-apidoc.tgz $(PRODUCT)-apidoc)
	-(cd $(STAGE_HOME) && $(ZIP) -rq $(PRODUCT)-apidoc.zip $(PRODUCT)-apidoc)
endif
	@echo "[DONE] Creating API documentation packages."

exports/zf2-documentation-$(VERSION).tar.gz: version
ifeq ($(wildcard exports/zf2-documentation-$(VERSION).tar.gz),)
	@echo "Downloading ZF2 documentation for version $(VERSION)..."
	-$(WGET) https://github.com/zendframework/zf2-documentation/archive/release-$(VERSION).tar.gz -O exports/zf2-documentation-$(VERSION).tar.gz
ifeq ($$?,0)
	@echo "Failed retrieving ZF2 documentation for version $(VERSION)"
	exit 1
endif
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
ifeq ($$?,0)
	@echo "Failed to build manual"
	exit 1
endif
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
	-cp $(STAGE_HOME)/ZendFramework*.epub "$(RELEASE_PATH)/"
	-cp $(STAGE_HOME)/ZendFramework*.pdf "$(RELEASE_PATH)/"
endif
	@echo "[DONE] Releasing Zend Framework $(VERSION)."

pyrus : pyrus-zf2 $(COMPONENTS)

pyrus-zf2 : zf2-export pyrus-verify $(PYRUS_HOME)
	@echo "Building Zend Framework pyrus package..."
ifeq ($(wildcard stage/pyrus/ZendFramework),)
	@echo "Generating package"
	-(cd $(PYRUS_HOME) && $(PYRUS_COMMAND) generate-pear2 ZendFramework $(PYRUS_CHANNEL))
	-rm -Rf $(PYRUS_HOME)/ZendFramework/src
	-cp -r $(PYRUS_META_TEMPLATES)/* $(PYRUS_HOME)/ZendFramework/
ifneq ($(wildcard stage/pyrus/ZendFramework/library),)
	-mv $(PYRUS_HOME)/ZendFramework/library $(PYRUS_HOME)/ZendFramework/src
endif
	-mv $(PYRUS_HOME)/ZendFramework/RELEASE-0.1.0 $(PYRUS_HOME)/ZendFramework/RELEASE-$(VERSION)
	-mv $(PYRUS_HOME)/ZendFramework/API-0.1.0 $(PYRUS_HOME)/ZendFramework/API-$(VERSION)
	-rm $(PYRUS_HOME)/ZendFramework/package_compatible.xml
	-rm $(PYRUS_HOME)/ZendFramework/stub.php
	-rm -Rf $(PYRUS_HOME)/ZendFramework/tests/
	-rm -Rf $(PYRUS_HOME)/ZendFramework/www/
	-rm -Rf $(PYRUS_HOME)/ZendFramework/example/
	-rm -Rf $(PYRUS_HOME)/ZendFramework/data/
endif
	-$(PHP) $(CURDIR)/bin/pyrus-create-metapackage-stub.php ZendFramework $(VERSION) $(EXPORT_HOME)/ZendFramework-$(VERSION)/composer.json $(PYRUS_TEMPLATES) $(PYRUS_HOME)/ZendFramework
ifeq ($$?,0)
	echo "Failed creating metapackage stub for ZendFramework Pyrus package"
	exit 1
endif
	-$(CURDIR)/bin/pyrus-make.sh ZendFramework "$(PYRUS_COMMAND)" $(PYRUS_HOME)
ifeq ($$?,0)
	@echo "Failed to compile Pyrus package"
	exit 1
endif
	-mv $(PYRUS_HOME)/ZendFramework/ZendFramework-$(VERSION).phar $(PYRUS_HOME)/
	-rm -Rf $(PYRUS_HOME)/ZendFramework/
	@echo "[DONE] Building Zend Framework pyrus package."

pyrus-verify:
	@$(PYRUS_COMMAND) list-channels | grep -q $(PYRUS_CHANNEL)
ifeq ($$?,0)
	@echo "The channel $(PYRUS_CHANNEL) is not in your channel list"
	exit 1
endif

$(PYRUS_HOME):
ifeq ($(wildcard stage/pyrus),)
	@echo "Creating staging directory for Pyrus"
	-mkdir $(PYRUS_HOME)
endif

pyrus-release: pyrus-verify
	@echo "Releasing Pyrus packages..."
ifeq ($(wildcard stage/pyrus/*.phar),)
	@echo "Nothing to release at this time"
else
	@for filename in $(PYRUS_HOME)/*.phar ; do \
		(cd $(PUBLIC) && $(PYRUS_COMMAND) scs-release $$filename) ; \
	done
endif
	@echo "[DONE] Releasing Pyrus packages."

zftool.phar:
	@echo "Creating zftool.phar..."
ifeq ($(wildcard exports/ZFTool-dev-master),)
	@echo "Exporting ZFTool package"
	-$(CURDIR)/bin/export-package.sh ZFTool dev-master $(PACKAGES) $(PHP) $(UNZIP) $(CURDIR) $(EXPORT_HOME)
ifeq ($$?,0)
	@echo "Failed to export package; check the version"
	exit 1
endif
endif
	-(cd $(EXPORT_HOME)/ZFTool-dev-master && $(PHP) -dmemory_limit=-1 -dphar.readonly=0 $(COMPOSER) install)
ifeq ($$?,0)
	@echo "Failed to install dependencies via Composer; aborting"
	exit 1
endif
	-(cd $(EXPORT_HOME)/ZFTool-dev-master && $(PHP) -dmemory_limit=-1 -dphar.readonly=0 bin/create-phar)
	-mv $(EXPORT_HOME)/ZFTool-dev-master/bin/zftool.phar $(STAGE_HOME)/
	@echo "[DONE] Creating zftool.phar."

zftool.phar-release: zftool.phar
	@echo "Releasing zftool.phar..."
	-cp $(STAGE_HOME)/zftool.phar $(PUBLIC)/
	@echo "[DONE] Releasing zftool.phar."

clean:
	-(cd $(STAGE_HOME) && rm -Rf *.phar *.zip *.tgz *.epub *pdf pyrus Zend*)
	-(cd $(EXPORT_HOME) && rm -Rf Zend* Component* ZF*)
