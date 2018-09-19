#Usage

Clone...
```bash
git clone https://github.com/danneaves/tomato.git
cd tomato
cp .env.dist .env
```

Open up `.env` and enter your details
```
TOMATO_BRANCHES=master,dev
TOMATO_PROJECTS=group1|one,two,three#group2|four,five,six

TOMATO_GIT_ORG=MyOrg
TOMATO_GIT_USER=user@email.com
TOMATO_GIT_PASS=secret
```

Run...
```bash
bin/tomato list
Tomato 0.1.0

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
  deploy            Deploy to a given server over ssh - extremely experimental!!
  help              Displays help for a command
  list              Lists commands
 git
  git:branch        Branch from one branch to another using the provided parameters
  git:merge         Merge from one branch to another using the provided parameters
  git:merge-down    Merge down the whole codebase using the provided parameters
  git:pull-request  Creates a new Pull Request using the provided parameters
```
