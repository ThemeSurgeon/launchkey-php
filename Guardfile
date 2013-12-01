#!/usr/bin/env ruby
#^syntax detection

guard 'bundler' do
  watch('Gemfile')
end

guard 'phpunit', tests_path: 'tests', cli: '--colors --bootstrap tests/bootstrap.php' do
  watch(%r{^.+Test\.php$})
  watch(%r{^lib/LaunchKey/(.+)\.php$}) { |m| "tests/Fury/Tests/#{m[1]}Test.php" }
end
