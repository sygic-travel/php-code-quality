#!/bin/sh

ROOT="$(cd "$(dirname "$0")"/..; pwd -P)"

echo "Installing GIT hooks"
rm -rf ${ROOT}/.git/hooks
ln -s ${ROOT}/git/hooks ${ROOT}/.git/hooks
chmod +x ${ROOT}/.git/hooks/*
