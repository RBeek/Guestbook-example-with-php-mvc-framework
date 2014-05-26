# A sample Guardfile
# More info at https://github.com/guard/guard#readme

# guard 'phpunit', :cli => '--colors' do
#   watch(%r{^tests/Framework/Tests/.+Test\.php$})
# end

guard 'livereload' do
  # watch(%r{app/views/.+\.(erb|haml|slim)$})
  # watch(%r{app/helpers/.+\.rb})
  watch(%r{public/.+\.(css|js|html|php)})
  # watch(%r{config/locales/.+\.yml})
  # Rails Assets Pipeline
  # watch(%r{(app|vendor)(/assets/\w+/(.+\.(css|js|html|png|jpg))).*}) { |m| "/assets/#{m[3]}" }
end
