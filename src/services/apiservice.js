import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'

const shareFile = async (file_id, recipient, recipient_type) => {
	return await axios.post(generateOcsUrl('apps/esig/api/v1/share'), {
		file_id,
		recipient,
		recipient_type,
	})
}

const getRequests = async (include_signed) => {
	return await axios.get(generateOcsUrl('apps/esig/api/v1/share'), {
		params: {
			include_signed,
		},
	})
}

const deleteRequest = async (id) => {
	return await axios.delete(generateOcsUrl('apps/esig/api/v1/share/' + id))
}

const getIncomingRequests = async (include_signed) => {
	return await axios.get(generateOcsUrl('apps/esig/api/v1/share/incoming'), {
		params: {
			include_signed,
		},
	})
}

const signRequest = async (id) => {
	return await axios.post(generateOcsUrl('apps/esig/api/v1/share/' + id + '/sign'))
}

const getOriginalUrl = (id) => {
	return generateUrl('apps/esig/download/' + id)
}

const getSignedUrl = (id) => {
	return generateUrl('apps/esig/download/signed/' + id)
}

export {
	shareFile,
	getRequests,
	getIncomingRequests,
	deleteRequest,
	signRequest,
	getOriginalUrl,
	getSignedUrl,
}
