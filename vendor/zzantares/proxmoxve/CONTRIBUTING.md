Welcome developers!
===================

Before making any contribution you should know:

- **Development of new features happens in the `develop` branch**, when this branch is stable enough it will merge with `master` and a new release will be made. Critical bug fixes can be applied directly to `master`.
- In your fork you can add changes to which ever branch you want, but if you submit a PR do so against the `develop` branch or as a new branch altogether. **Any PR made to the `master` branch will be rejected (unless it contains a critical bug fix)**.
- All **code contribution should follow PSR-1 and PSR-2** coding style standards. (Thanks to [Alexey Ashurok](https://github.com/aotd1))
- You should **code the tests for any function you add**, some times is not possible but try doing it. Personally I like more the *black box* testing approach.
- **All functions need to be properly documented**, all comments, variable names, function names only on english language.
- Variables and functions names should be self descriptive.


Installation
------------

Of course you should [fork the repo](https://github.com/ZzAntares/ProxmoxVE/fork), then after cloning your forked repo:

```sh
$ composer install --dev  # Run command inside the project folder
```

Using docker
------------

If you have another conflicting PHP setup or you don't have any setup at all and you just want to code, you can use the `zzantares/php-proxmoxve` [docker image](https://hub.docker.com/r/zzantares/php-proxmoxve/) to have a complete development environment.

After you have cloned your forked project, and with docker installed on your machine, inside the project directory, just run:

``` sh
$ docker pull zzantares/php-proxmoxve
$ docker run -v $(pwd):/root/proxmoxve -it zzantares/php-proxmoxve
```

Alternatively, the repository ships with a `Dockerfile` which also can be used by contributors in order to build that same image instead of pulling from the docker.io registry.

``` sh
$ docker build -t php-proxmoxve .
$ docker run -v $(pwd):/root/proxmoxve -it php-proxmoxve
```

Inside the container you have all the PHP extensions needed to develop PHP code for this project. The only step left is to install the project dependencies with composer:

``` sh
$ composer install  # Run inside the container
```

Remember to use the container only to test the application, you can still code and commit in your local computer. The container only provides the environment.

What needs to be done?
----------------------

What ever you think will improve this library and also you could check out the open issues. Any help is gladly welcome, thanks for your consideration!
