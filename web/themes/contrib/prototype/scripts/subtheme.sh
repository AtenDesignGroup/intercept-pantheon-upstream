#!/bin/bash

set -e

basetheme="STARTER_KIT"
newtheme="STARTER_KIT"
target_dir="."
basetheme_dir=$(pwd)"/STARTER_KIT"
newtheme_dir="$target_dir/$newtheme"

main() {
  configure $@
  confirm
  make_subtheme
}

configure() {
  local newtheme_ovrrd=$1
  local target_dir_ovrrd=$2
  newtheme=${newtheme_ovrrd:=$newtheme}
  target_dir=${target_dir_ovrrd:=$target_dir}
  newtheme_dir="$target_dir/$newtheme"
  newthemekebab=$(echo "$newtheme" | sed -e "s%_%-%g")
  basethemekebab=$(echo "$basetheme" | sed -e "s%_%-%g")
}

confirm() {
  info "Generating a new theme named %s in the directory %s\n" $newtheme $target_dir
  read -p "Continue? [Y/n]: " confirmed
  if [[ ! $confirmed =~ ^[Yy]$ ]]; then
    printf "Cancelled.\n"
    exit 0
  else
    printf "Generating new subtheme in %s/%s...\n" $target_dir $newtheme
    sleep 1
  fi
}


make_subtheme() {
  mkdir -p $target_dir
  copy $basetheme_dir $newtheme_dir
  cd $newtheme_dir
  list_files_like $basetheme\.\* | bulk_rename "$basetheme" "$newtheme"
  cleanup

  info "Replacing %s with %s...\n" $basetheme $newtheme
  find . -type f -name '*.yml' -o -name '*.twig' -o -name '*.theme' | xargs sed -i '' "s%$basetheme%$newtheme%g"

  info "Replacing %s with %s...\n" $basethemekebab $newthemekebab
  find . -type f  -name '*.yml' -o -name '*.twig' -o -name '*.theme' | xargs sed -i '' "s%$basethemekebab%$newthemekebab%g"

  info "Installing node dependencies"
  npm install
  npm run css
  npm run js
  npm run svg-sprite
  info "Done!\n"
}

cleanup() {
  remove=( \
    "./.git" \
    "./build" \
    "./templates/ui_patterns" \
    "./node_modules" \
    "./package-lock.json" \
  )

  for file in ${remove[@]}; do
    remove $file
  done
}

list_files_like() {
  find . -name "$1"
}

copy() {
	info "Copying %s to %s...\n" $1 $2
  rm -rf /tmp/$newtheme
  cp -r $1 /tmp/$newtheme
  mv /tmp/$newtheme $2
}

remove() {
	info "Removing %s\n" $1
  rm -rf $1
}

# Filenames read from stdin, replaces pattern (arg 1) in filename w/ replace (arg 2).
bulk_rename() {
	info "Bulk renaming files matching \"%s\" with \"%s\"...\n" "$1" "$2"
  sed "p;s%$1%$2%g" | xargs -n2 mv
}

# Replace pattern (arg 1) w/ replace (arg 2) in filename (arg 3)
replace() {
	info "Replacing occurences of \"%s\" with \"%s\" in %s...\n" "$1" "$2" "$3"
  sed -i '' s%$1%$2%g $3
}

info() {
	(>&2 printf "$1" ${@:2})
}

main $@
