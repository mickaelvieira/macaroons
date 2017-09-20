OS           := $(shell uname -s)
PATH         := bin:$(PATH)
SHELL        := /bin/bash
SOURCE_DIR   := src
LINTER_FLAGS := --standard=PSR2 --ignore=compatibility.php,functions.php

.PHONY: all clean install test fmt lint

all: clean install

clean:
	rm -rf vendor/*

install:
	composer install

lint:
	phpcs $(LINTER_FLAGS) $(SOURCE_DIR)

fmt:
	phpcbf $(LINTER_FLAGS) $(SOURCE_DIR)

test:
	phpspec run --format=pretty -vvv

branch:
	create-branch

release:
	create-release
