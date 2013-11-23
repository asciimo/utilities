#! /usr/bin/env python

import sys
import os
import re
import getopt

""" Globals """
verbose = False
recursive = False
ignore_patterns = []

def main():

    global verbose, recursive, ignore_patterns

    directory = "."

    try:
        opts, args = getopt.getopt(sys.argv[1:], "", ["help", "directory=",
        "ignore=", "verbose", "recursive"])
    except getopt.GetoptError as err:
        # print help information and exit
        print str(err)
        usage()
        sys.exit(2)

    if not len(opts):
        usage()
        sys.exit(2)

    for o, a in opts:
        if o == "--directory":
            directory = a
        elif o == "--recursive":
            recursive = True
        elif o == "--verbose":
            verbose = True
        elif o == "--ignore":
            init_ignore_patterns(a)
        elif o == "--help":
            usage()
            sys.exit(0)

    get_files(directory)


def usage():
    print("usage: cdn_update.py [--directory=<directory>] [--ignore=<filename>] [--recursive] [--help] [--verbose]");

def init_ignore_patterns(filename):
    """Compiles each pattern in filename into a regular expression,
    and stores the results in the global ignore_patterns list."""
    global ignore_patterns
    empty_line = re.compile('^\s*$')
    comment_line = re.compile('^\s*#')
    try:
        with open(filename) as f:
            for line in f:
                if(empty_line.match(line) or comment_line.match(line)):
                    continue
                ignore_patterns.append(re.compile(line.strip()))
    except (IOError):
        print "Unable to open %s" % filename
        sys.exit();

def get_files(directory):

    global verbose, recursive, ignore_patterns
    total_replacements = 0

    for root, dirs, files in os.walk(directory, topdown=True):
        # Filter the directories and files in place
        files[:] = [f for f in files if not any(p.search(f) for p in ignore_patterns)]
        dirs[:] = [d for d in dirs if not any(p.search(d) for p in ignore_patterns)]
        for filename in files:
            path = os.path.join(root, filename)
            if verbose:
                print "Processing: %s" % path
            replacements = replace(path)
            total_replacements += replacements
            if verbose:
                print "Replaced %d instances in %s (%s total)" % (replacements, path, total_replacements)
        for dir in dirs:
            path = os.path.join(root, dir)
            if verbose:
                print "Directory: %s" % path
        if recursive == False:
            break;

def replace(path):
    match_count = 0

    with open(path, 'r') as f:
        content = f.read()

    # First, see if there are any easily replaceable image paths
    any_image_search = re.compile(r'([\'"])(.*?\.(jpg|jpeg|gif|png))\1', flags=re.IGNORECASE)
    image_search_results = any_image_search.findall(content)

    if len(image_search_results) == 0:
        return 0

    # Replace image paths that contain the domain name
    domain_search = re.compile(r'([\'"])http://www\.happycow\.net/(.*?\.(jpg|jpeg|gif|png))\1', flags=re.IGNORECASE)
    results = domain_search.subn(r'\1//{$static_host}/\2\1', content)
    content = results[0]
    match_count += results[1]

    # Replace root-relative image paths
    root_relative_search = re.compile(r'([\'"])/(?!/{$static_host})(.*?\.(jpg|jpeg|gif|png))\1', flags=re.IGNORECASE)
    results = root_relative_search.subn(r'\1//{$static_host}/\2\1', content)
    content = results[0]
    match_count += results[1]

    with open(path, 'w') as f:
        f.write(content)

    return match_count


if __name__ == "__main__":
    main()
