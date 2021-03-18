# Installing

Clone...
```bash
git clone https://github.com/danneaves/tomato.git
cd tomato
composer install -a
cp .env.dist .env
```

# Configuring
## .env

The .env file holds your credentials for communicating with git.

Open up `.env` and enter your details
```
TOMATO_GIT_ORG=MyOrg
TOMATO_GIT_USER=user@email.com
TOMATO_GIT_PASS=secret
```

Note that TOMATO_GIT_PASS is a git personal access token. You can set one up by logging into github and going to this
page: https://github.com/settings/tokens

The access token needs to have the following permissions: gist, repo, workflow

## tomato.json

The tomato.json file holds information about the git projects you wish to manage.

The general structure of the file is as follows:

```json
{
    "branches": [
        "master",
        "uat",
        "test_trunk",
        "dev_trunk"
    ],
    "projects": {
        "root": [
            "tomato"
        ],
        "sub": [
            "foo"
        ],
        "semver": [
            "bar"
        ]
    }
}
```

### Branches

```json
{
    "branches": [
        "branch-name1",
        ...
        "branch-nameX"
    ]
}
```

Branches is optional, and is only required by the merge down feature of tomato.

This is an array of the branch structure you may use for non-semver projects.

For example, some projects may not be using semver, and instead rely on multiple branches for versioning.

Defining this structure here means, if you perform a "merge down" command from master, tomato will merge, in the above
example, from master to uat to test_trunk to dev_trunk.

### Projects

```json
{
    "projects": {
        "root": [
            "project1",
            ...
            "projectX"
        ],
        "sub": [
            "sub1",
            ...
            "subX"
        ],
        "semver": [
            "semver1",
            ...
            "semverX"
        ]
    }
}
```

This is an object that allows logical grouping of projects groups.

A group is simply a list of projects upon which we may want to perform particular operations.

In the above example, we've used "root", "sub" and "semver" to define three different groups.

You can define your own groups if you wish. The only required group is "root", and even then, only for deploy
functionality.

The primary usage of defining projects is to be able to perform "bulk" operations.

Any command that allows a `--scope=<group>` argument relies on that group being defined here.

For example, if you wanted to merge two branches that existed on all your root projects, you
could do:

```shell
bin/tomato git:merge -sdevelop -dmaster --scope=root
```

Note: you can use the group "all" as a scope to tell tomato that the operation you are about to do must be performed
for all groups.

# Usage
## Getting help
### Generic help
```shell
bin/tomato --help
```

### Help about a command

```shell
bin/tomato <command> --help
```

For example, to get help about `git:branch`:

```shell
bin/tomato git:branch --help
```

## List all available commands

```shell
bin/tomato list
```

Example output:
```bash
Tomato 1.0.0

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  config             Configure the branching and project lists
  deploy             Deploy to a given server over ssh - extremely experimental!!
  help               Displays help for a command
  list               Lists commands
 git
  git:branch         Branch from one branch to another using the provided parameters
  git:list-projects  List all git projects
  git:merge          Merge from one branch to another using the provided parameters
  git:merge-down     Merge down the whole codebase using the provided parameters
  git:pull-request   Creates a new Pull Request using the provided parameters
  git:tag            Control tags for a project or for a group of projects
```

## Branching

```bash
bin/tomato git:branch --help
Description:
  Branch from one branch to another using the provided parameters

Usage:
  git:branch [options]

Options:
  -s, --source=SOURCE        Source branch
  -a, --name=NAME            Name for the branch
      --scope[=SCOPE]        Project scope: all|root|sub [default: "all"]
      --delete[=DELETE]      Delete the branch named in `name` [default: false]
  -p, --projects[=PROJECTS]  Array of projects, comma separated (multiple values allowed)
  -h, --help                 Display this help message
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi                 Force ANSI output
      --no-ansi              Disable ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Examples
#### Create a new branch across all root projects
```shell
bin/tomato git:branch --source=master --name=branch --scope=root
```

## List Projects
```bash
bin/tomato git:list-projects --help
Description:
  List all git projects

Usage:
  git:list-projects

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
### Examples
#### List all projects
```shell
bin/tomato git:list-projects
```

## Merging
```bash
bin/tomato git:merge --help
Description:
  Merge from one branch to another using the provided parameters

Usage:
  git:merge [options]

Options:
  -s, --source=SOURCE            Source branch
  -d, --destination=DESTINATION  Destination branch
      --scope[=SCOPE]            Project scope: all|root|sub [default: "all"]
  -p, --projects[=PROJECTS]      Array of projects, comma separated (multiple values allowed)
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Examples
#### Merging two branches that exist across all root projects
```shell
bin/tomato git:merge --source=branch1 --destination=branch2 --scope=root
```
#### Merge two branches for a specific project
```shell
bin/tomato git:merge --source=branch1 --destination=branch2 --projects=project-name
```

## Merging Down
```bash
bin/tomato git:merge-down --help
Description:
  Merge down the whole codebase using the provided parameters

Usage:
  git:merge-down [options]

Options:
  -s, --source[=SOURCE]            Source branch
  -d, --destination[=DESTINATION]  Destination branch
      --scope[=SCOPE]              Project scope: [all] [default: "all"]
  -t, --then[=THEN]                Other branch to merge down to
  -h, --help                       Display this help message
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi                       Force ANSI output
      --no-ansi                    Disable ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
### Examples
#### Merge down all root projects
```shell
bin/tomato git:merge-down --scope=root
```
#### Merge down all root projects as well as to an extra branch
```shell
bin/tomato git:merge-down --scope=root --then=extra-branch
```

## Pull Requests
```bash
bin/tomato git:pull-request --help
Description:
  Creates a new Pull Request using the provided parameters

Usage:
  git:pull-request [options]

Options:
  -s, --source=SOURCE            Source branch
  -d, --destination=DESTINATION  Destination branch
      --scope[=SCOPE]            Project scope: [all] [default: "all"]
  -p, --projects[=PROJECTS]      Array of projects, comma separated (multiple values allowed)
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
### Examples
#### Create a pull request for two branches across all root projects
```shell
bin/tomato git:pull-request --source=branch1 --destination=branch2 --scope=root
```
#### Create a pull request for a specific project
```shell
bin/tomato git:pull-request --source=branch1 --destination=branch2 --projects=project-name
```

## Tags
```bash
bin/tomato git:tag --help
Description:
  Control tags for a project or for a group of projects

Usage:
  git:tag [options]

Options:
  -s, --source[=SOURCE]                  Source branch
  -a, --name[=NAME]                      Name for the tag
  -y, --confirm                          Answer yes to all questions
      --scope[=SCOPE]                    Project scope: all|root|sub [default: "all"]
      --delete[=DELETE]                  Delete the tag named in `name`, you may specify a range to remove [default: false]
      --prune[=PRUNE]                    Prune all tags below latest major versions [default: false]
      --safe-prune[=SAFE-PRUNE]          Only prune non-stable versions [default: true]
      --beta-release[=BETA-RELEASE]      Construct a beta from the latest alpha in a range of projects [default: false]
      --rc-release[=RC-RELEASE]          Construct a release candidate from the latest beta in a range of projects [default: false]
      --stable-release[=STABLE-RELEASE]  Construct a stable release from the latest release candidate in a range of projects [default: false]
  -p, --projects[=PROJECTS]              Array of projects, comma separated (multiple values allowed)
  -h, --help                             Display this help message
  -q, --quiet                            Do not output any message
  -V, --version                          Display this application version
      --ansi                             Force ANSI output
      --no-ansi                          Disable ANSI output
  -n, --no-interaction                   Do not ask any interactive question
  -v|vv|vvv, --verbose                   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

The idea behind `git:tag` is to provide a way of promoting releases based on semver.

So, for example, you can promote all `.beta` style release to `.rc`, or promote all `.rc` tags to stable releases.

### Examples
#### Create a release candidate for all semver projects
```shell
bin/tomato git:tag --rc-release --scope=semver
```
#### Create a stable release for a specific project
```shell
bin/tomato git:tag --stable-release --projects=project-name
```
