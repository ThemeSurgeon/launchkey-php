ROOT       = File.expand_path('..', __FILE__)
PAGES_ROOT = File.join(ROOT, 'gh-pages')

namespace :doc do
  task :generate do
    puts 'Generating docs...'
    Dir.chdir ROOT do
      sh 'apigen --config apigen.neon'
    end
  end

  desc 'Commit generated docs to the gh-pages branch and publish to GitHub Pages'
  task publish: :generate do
    # Ensure the gh-pages dir exists so we can generate into it.
    puts 'Checking for gh-pages dir...'
    unless File.exist?(PAGES_ROOT)
      puts 'No gh-pages directory found. Run the following commands first:'
      puts '  `git clone git@github.com:LaunchKey/launchkey-php gh-pages'
      puts '  `cd gh-pages'
      puts '  `git checkout gh-pages`'
      exit(1)
    end

    # Ensure gh-pages branch is up to date.
    Dir.chdir PAGES_ROOT do
      sh 'git pull origin gh-pages'
    end

    # Copy to gh-pages dir.
    puts 'Copying site to gh-pages branch...'
    Dir.chdir ROOT do
      sh "rsync -a --delete-after --exclude '.*' docs/ gh-pages/"
    end

    # Commit and push.
    puts 'Committing and pushing to GitHub Pages...'
    sha = `git log`.match(/[a-z0-9]{40}/)[0]
    Dir.chdir PAGES_ROOT do
      sh 'git add -u .'
      sh 'git add .'
      sh "git commit -m 'Update to #{sha}.'"
      sh 'git push origin gh-pages'
    end
    puts 'Done.'
  end
end
