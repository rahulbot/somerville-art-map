require 'open-uri'
require 'hpricot'
require 'digest/md5'
require 'geocoder'
require 'csv'
require 'json'

CACHE_DIR = "cache"
GEOCODE_CACHE_FILE = File.join CACHE_DIR, "geocode-cache.json"
OVERRIDE_CACHE = false
BASE_URL = "http://www.somervilleartscouncil.org/artmap"
NEIGHBORHOOD_BASE_URL = "http://www.somervilleartscouncil.org/artmap?field_public_art_neighborhood_value_many_to_one%5B%5D="

# wrapper around a cache a webpages (name id md5 hash of url, contents are raw HTML)
def cached_wget(url, override_cache=false)
	print "  Fetching #{url}"
	cache_file_name = File.join CACHE_DIR, Digest::MD5.hexdigest(url)
	if override_cache or not File.exists? cache_file_name
		File.open(cache_file_name, 'w') do |cache_file|  
			open(url) do |remote_file|
				remote_file.each_line do |remote_line| 
					cache_file.puts remote_line
				end
			end
		end 
		puts " (downloaded fresh copy to #{cache_file_name})"
	else
		puts " (loaded from cache #{cache_file_name})"
	end
	cache_file_name
end

# prep the geocode cache
$geocode_cache = {}
if File.exists? GEOCODE_CACHE_FILE
	file_text = File.read(GEOCODE_CACHE_FILE)
	$geocode_cache = JSON.parse(file_text)
end

def cached_geocode(address, override_cache=false)
	key = Digest::MD5.hexdigest(address)
	geo_info = nil
	if $geocode_cache.has_key? key
		geo_info = $geocode_cache[key]
	else
		geo_info = Geocoder.search(address)
		if geo_info.length>0 
			$geocode_cache[key] = geo_info[0].data
			File.open(GEOCODE_CACHE_FILE,"w") do |f| # write after each lookup
	  		f.write($geocode_cache.to_json)
			end
			geo_info = $geocode_cache[key]
		end
		sleep 1 # important so we don't hit the quota on Google
	end
	geo_info
end

puts "Scraping Somerville art map info:"
art_items = []

# first get all the neighborhood names
doc = Hpricot(open(cached_wget BASE_URL))
neighborhoods = (doc / "select[@id='edit-field-public-art-neighborhood-value-many-to-one'] > option").collect do |option| 
	option.inner_text
end
neighborhoods.uniq!
puts "  Found #{neighborhoods.count} neighborhoods:"
neighborhoods.each { |name| puts "    "+name }
doc = nil

# now do neighborhood by neighboorhood
neighborhoods.each do |neighborhood|
	puts "Scraping art in #{neighborhood}"
	doc = Hpricot(open(cached_wget NEIGHBORHOOD_BASE_URL+URI::encode(neighborhood)))
	rows = doc / "table.views-table tr"
	if rows.length > 0
		puts "  found #{rows.length} items"
		rows.each do |row|
			item = {}
			cells = row / "td"
			next if cells.length == 0 # header row has only th's
			item['name'] = (cells[1] / "a").first.innerText.strip
			item['neighborhood'] = neighborhood
			item['artist'] = cells[2].innerText.strip
			item['address'] = nil
			item['address'] = (cells[3] / "div.street-address").first.innerText.strip if (cells[3] / "div.street-address").first
			item['address']+= " , Somerville, MA" if not item['address'].nil? and not item['address'].include? "Somerville"
			item['created_date'] = cells[4].innerText.strip
			item['thumbnail_url'] = (cells[0] / "a > img").first.attributes['src'] unless (cells[0] / "a > img").first.nil?
			item['medium_url'] = item['thumbnail_url'].gsub('public_art_view','porchfest_photo') unless item['thumbnail_url'].nil?
			item['full_url'] = item['thumbnail_url'].gsub('imagecache/public_art_view/','') unless item['thumbnail_url'].nil?
			item['latitude'] = nil
			item['longitude'] = nil
			# and geocode it!
			unless item['address'].nil?
				# TODO: cache these in a local file (from address to results json perhaps)
				geo_info = cached_geocode item['address']
				unless geo_info.nil? or geo_info.length==0
					item['latitude'] = geo_info['geometry']['location']['lat']
					item['longitude'] = geo_info['geometry']['location']['lng']
				end
			end
			art_items << item
		end
	end
	doc = nil
end

puts "Done - found #{art_items.length} items"

# and write it all out to a CSV
CSV_FILENAME = "somerville-public-art-list.csv"
print "Writing to csv #{CSV_FILENAME}..."
CSV.open(CSV_FILENAME, "wb") do |csv|
  keys = art_items[0].keys
  csv << keys
  art_items.each do |item|
	  csv << keys.collect { |key| item[key] }
  end
end
puts " done"

# and write it all to JSON
JSON_FILENAME = "somerville-public-art-list.json"
print "Writing to JSON #{JSON_FILENAME}..."
File.open(JSON_FILENAME,"w") do |f| # write after each lookup
	f.write(art_items.to_json)
end
puts " done"

puts "Finished"
