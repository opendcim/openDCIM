openDCIM
-----------


	An Open Source Software package for managing the infrastructure of a 
	data center, no matter how small or large.  Initially developed 
	in-house at Vanderbilt University Information Technology Services by 
	Scott Milliken.  

	After leaving Vanderbilt for Oak Ridge National Laboratory, Vanderbilt 
	granted permission for the package to be open sourced under GPLv3.  
	Scott continues as the primary contributor to the package and is 
	actively recruiting assistance from others.

        This program is free software:  you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published
        by the Free Software Foundation, version 3.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        For further details on the license, see http://www.gnu.org/licenses


Contribution
---
Contributions are always welcome, please follow these steps to submit your changes:

1. Install git from http://git-scm.com/
2. Create a github account on https://github.com
3. Set up your git ssh key using these instructions http://help.github.com/set-up-git-redirect
4. Open the jQuery Validation Engine project home page on github on https://github.com/samilliken/openDCIM/
5. Click the "Fork" button, this will get you to a new page: your own copy of the code.
6. Copy the SSH URL at the top of the page and clone the repository on your local machine

    ```shell
    git clone git@github.com:your-username/openDCIM.git my-opendcim-repo
    ```

7. Create a branch and switch to it

    ```shell
    cd my-opendcim-repo
    git branch mynewfeature-patch
    git checkout mynewfeature-patch
    ```

8. Apply your changes, then commit using a meaningful comment, that's the comment everybody will see!

    ```shell
    git add .
    git commit -m "Fixing issue 157, blablabla"
    ```

9. Push the changes back to github (under a different branch, here myfeature-patch)

    ```shell
    git push origin mynewfeature-patch
    ```

10. Open your forked repository on github at https://github.com/your-username/openDCIM
11. Click "Switch Branches" and select your branch (mynewfeature-patch)
12. Click "Pull Request"
13. Submit your pull request to the openDCIM Developers
